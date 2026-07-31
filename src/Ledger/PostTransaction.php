<?php

namespace Walletable\Ledger;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Walletable\Events\TransactionConfirmed;
use Walletable\Events\TransactionCreating;
use Walletable\Events\TransactionPending;
use Walletable\Events\TransactionPosted;
use Walletable\Exceptions\IdempotencyConflictException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Walletable;
use Walletable\Internals\Lockers\LockerInterface;
use Walletable\Models\HouseAccount;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

/**
 * The only primitive that writes the ledger. Every business event composes
 * a TransactionDraft and hands it here; balance updates, posting rows, and
 * the transaction row all land inside one DB transaction.
 *
 * The wallets.amount cache is kept in sync via the configured locker.
 */
class PostTransaction
{
    protected ?LockerInterface $locker;

    public function __construct(?LockerInterface $locker = null)
    {
        $this->locker = $locker;
    }

    /**
     * Persist a draft as a pending or posted transaction. Pending transactions
     * skip the postings + balance phase and stash drafts for later confirmation.
     */
    public function execute(TransactionDraft $draft): Transaction
    {
        $draft->assertBalanced();

        if ($replay = $this->findReplay($draft)) {
            return $replay;
        }

        $this->assertAffordable($draft);

        App::make('events')->dispatch(new TransactionCreating($draft));

        try {
            $tx = DB::transaction(function () use ($draft) {
                $tx = $this->makeTransaction($draft);
                $tx->save();

                if ($draft->status === Transaction::STATUS_POSTED) {
                    $this->writePostings($tx, $draft->postings);
                }

                return $tx;
            });
        } catch (QueryException $e) {
            // A concurrent caller won the race for this key; the unique index
            // rejected our insert and the whole transaction rolled back, so the
            // winner's row is the correct result to hand back.
            if ($draft->idempotencyKey !== null && $this->isUniqueViolation($e)) {
                if ($replay = $this->findReplay($draft)) {
                    return $replay;
                }
            }

            throw $e;
        }

        if ($tx->isPosted()) {
            App::make('events')->dispatch(new TransactionPosted($tx));
        } elseif ($tx->isPending()) {
            App::make('events')->dispatch(new TransactionPending($tx));
        }

        return $tx;
    }

    /**
     * Transition a pending transaction to posted: rehydrate the parked drafts,
     * apply balance changes, write postings, clear the draft column.
     *
     * Safe to call twice. A transaction already posted — whether by an earlier
     * call or by one racing this one — comes back untouched, with its postings
     * written once and its events fired once.
     */
    public function confirm(Transaction $tx): Transaction
    {
        if ($tx->isPosted()) {
            return $tx;
        }

        if (!$tx->isPending()) {
            $this->refuseTransition($tx, 'confirmed');
        }

        $drafts = $this->parkedPostings($tx);

        if (empty($drafts)) {
            throw new \RuntimeException('Pending transaction has no parked postings to confirm.');
        }

        $claimed = false;

        DB::transaction(function () use ($tx, $drafts, &$claimed) {
            $claimed = $this->claimPending($tx, [
                'status' => Transaction::STATUS_POSTED,
                'posted_at' => now(),
                'draft_postings' => null,
            ]);

            if ($claimed) {
                $this->writePostings($tx, $drafts);
            }
        });

        $tx->refresh();

        if (!$claimed) {
            // Lost the claim: someone moved the row out of pending underneath us.
            // Posted is the outcome we were after, so hand it back as a replay.
            // Voided is not, and the caller has to hear about it.
            if (!$tx->isPosted()) {
                $this->refuseTransition($tx, 'confirmed');
            }

            return $tx;
        }

        App::make('events')->dispatch(new TransactionConfirmed($tx));
        App::make('events')->dispatch(new TransactionPosted($tx));

        return $tx;
    }

    /**
     * Void a pending transaction without ever writing its postings or moving
     * balances. Safe to call twice, and refuses to void a transaction that a
     * concurrent confirm has already posted.
     */
    public function void(Transaction $tx): Transaction
    {
        if ($tx->isVoided()) {
            return $tx;
        }

        if (!$tx->isPending()) {
            $this->refuseTransition($tx, 'voided');
        }

        $claimed = $this->claimPending($tx, [
            'status' => Transaction::STATUS_VOIDED,
            'draft_postings' => null,
        ]);

        $tx->refresh();

        if (!$claimed && !$tx->isVoided()) {
            $this->refuseTransition($tx, 'voided');
        }

        return $tx;
    }

    /**
     * Move the row out of pending in one statement, so that two callers racing
     * over the same pending transaction cannot both go on to write its
     * postings. False means another caller won and nothing was written.
     *
     * This writes through the query builder, bypassing the model's transition
     * guard: the guard compares against in-memory state, which is exactly the
     * stale read being defended against here. The `status` predicate and the
     * fixed set of columns keep the same invariant.
     */
    protected function claimPending(Transaction $tx, array $attributes): bool
    {
        return $tx->newQuery()
            ->whereKey($tx->getKey())
            ->where('status', Transaction::STATUS_PENDING)
            ->update($attributes) === 1;
    }

    /**
     * @return PostingDraft[]
     */
    protected function parkedPostings(Transaction $tx): array
    {
        $raw = $tx->getRawOriginal('draft_postings');

        return $raw ? array_map(
            fn($d) => PostingDraft::fromArray($d),
            is_array($raw) ? $raw : (json_decode($raw, true) ?? [])
        ) : [];
    }

    /**
     * @throws \RuntimeException always
     */
    protected function refuseTransition(Transaction $tx, string $verb): void
    {
        throw new \RuntimeException(sprintf(
            'Only pending transactions can be %s; this one is %s.',
            $verb,
            $tx->getRawOriginal('status')
        ));
    }

    /**
     * Resolve a draft that carries an idempotency key against what is already
     * stored under it: no row means this is a first attempt, a matching row is
     * a safe replay, and a row describing different money is a conflict.
     */
    protected function findReplay(TransactionDraft $draft): ?Transaction
    {
        if ($draft->idempotencyKey === null) {
            return null;
        }

        $existing = $this->findByIdempotencyKey($draft->idempotencyKey);

        if ($existing === null) {
            return null;
        }

        if ($existing->getRawOriginal('idempotency_hash') !== $draft->fingerprint()) {
            throw new IdempotencyConflictException($existing, $draft->idempotencyKey);
        }

        return $existing;
    }

    protected function findByIdempotencyKey(string $key): ?Transaction
    {
        return config('walletable.models.transaction')::query()
            ->where('idempotency_key', $key)
            ->first();
    }

    /**
     * SQLSTATE class 23 is "integrity constraint violation" on every supported
     * engine. It is deliberately broad: the caller only acts on it when a row
     * for the key then turns up, and rethrows otherwise.
     */
    protected function isUniqueViolation(QueryException $e): bool
    {
        return str_starts_with((string) ($e->errorInfo[0] ?? ''), '23');
    }

    protected function makeTransaction(TransactionDraft $draft): Transaction
    {
        /** @var Transaction $tx */
        $tx = App::make(config('walletable.models.transaction'));

        $tx->forceFill(array_merge([
            'currency' => $draft->currency,
            'status' => $draft->status,
            'idempotency_key' => $draft->idempotencyKey,
            'idempotency_hash' => $draft->idempotencyKey === null ? null : $draft->fingerprint(),
            'posted_at' => $draft->status === Transaction::STATUS_POSTED ? now() : null,
            'narration' => $draft->narration,
            'reference_type' => $draft->referenceType,
            'reference_id' => $draft->referenceId,
            'meta' => $draft->meta,
            'draft_postings' => $draft->status === Transaction::STATUS_PENDING
                ? $draft->postingsToArray()
                : null,
            'created_at' => now(),
        ], $this->extraAttributes($draft)));

        return $tx;
    }

    /**
     * Extra columns staged on the draft by the caller or an action. Only the
     * columns the application declared through Walletable::extendTransaction()
     * are written; anything else is a programming error.
     *
     * @return array<string,mixed>
     */
    protected function extraAttributes(TransactionDraft $draft): array
    {
        if (empty($draft->attributes)) {
            return [];
        }

        $unknown = array_diff(array_keys($draft->attributes), Walletable::transactionColumns());

        if (!empty($unknown)) {
            throw new UnregisteredTransactionColumnException(sprintf(
                'Undeclared transaction column(s): %s. Declare them with ' .
                'Walletable::extendTransaction([...]) in a service provider and add ' .
                'them to your transactions table.',
                implode(', ', $unknown)
            ));
        }

        return $draft->attributes;
    }

    /**
     * Refuse a draft that would overdraw a user wallet, before any event fires
     * or any row is written.
     *
     * The locker enforces the same invariant when it applies each posting, and
     * remains the authority; this only brings the failure forward to a cheaper
     * point. It has to sit behind the idempotency lookup: a retry of a debit
     * that already drained the wallet is a replay, not an overdraft.
     *
     * Pending drafts move no balance, so they are checked at confirmation
     * instead — that is what makes an authorisation possible.
     */
    protected function assertAffordable(TransactionDraft $draft): void
    {
        if ($draft->status !== Transaction::STATUS_POSTED) {
            return;
        }

        foreach ($draft->postings as $posting) {
            if ($posting->isCredit() || $this->isHouseWallet($posting->wallet)) {
                continue;
            }

            if ($posting->wallet->amount->lessThan($posting->amount)) {
                throw new InsufficientBalanceException($posting->wallet, $posting->amount);
            }
        }
    }

    /**
     * Apply each draft to its wallet's balance via the locker and write the
     * resulting posting row.
     *
     * @param PostingDraft[] $drafts
     */
    protected function writePostings(Transaction $tx, array $drafts): void
    {
        $locker = $this->resolveLocker();
        $postingClass = config('walletable.models.posting');
        $now = now();

        foreach ($drafts as $draft) {
            $allowNegative = $this->isHouseWallet($draft->wallet);
            $balanceAfter = $locker->apply($draft->wallet, $draft->direction, $draft->amount, $allowNegative);

            $posting = App::make($postingClass);
            $posting->forceFill([
                'transaction_id' => $tx->getKey(),
                'wallet_id' => $draft->wallet->getKey(),
                'direction' => $draft->direction,
                'amount' => $draft->amount->integer(),
                'currency' => $draft->amount->getCurrency()->getCode(),
                'balance_after' => $balanceAfter,
                'action' => $draft->action,
                'method_id' => $draft->methodId,
                'method_type' => $draft->methodType,
                'meta' => $draft->meta ?: null,
                'posted_at' => $now,
            ])->save();

            $draft->balanceAfter = $balanceAfter;
        }
    }

    protected function isHouseWallet(Wallet $wallet): bool
    {
        $type = $wallet->getRawOriginal('walletable_type');
        return $type === HouseAccount::class
            || $type === 'house_account'
            || ($wallet->walletable_type ?? null) === HouseAccount::class;
    }

    protected function resolveLocker(): LockerInterface
    {
        if ($this->locker) {
            return $this->locker;
        }
        return $this->locker = Walletable::locker(config('walletable.locker'));
    }
}
