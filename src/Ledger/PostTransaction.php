<?php

namespace Walletable\Ledger;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Walletable\Events\TransactionConfirmed;
use Walletable\Events\TransactionCreating;
use Walletable\Events\TransactionPending;
use Walletable\Events\TransactionPosted;
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

        App::make('events')->dispatch(new TransactionCreating($draft));

        $tx = DB::transaction(function () use ($draft) {
            $tx = $this->makeTransaction($draft);
            $tx->save();

            if ($draft->status === Transaction::STATUS_POSTED) {
                $this->writePostings($tx, $draft->postings);
            }

            return $tx;
        });

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
     */
    public function confirm(Transaction $tx): Transaction
    {
        if (!$tx->isPending()) {
            throw new \RuntimeException(sprintf(
                'Only pending transactions can be confirmed; this one is %s.',
                $tx->getRawOriginal('status')
            ));
        }

        $raw = $tx->getRawOriginal('draft_postings');
        $drafts = $raw ? array_map(
            fn($d) => PostingDraft::fromArray($d),
            is_array($raw) ? $raw : (json_decode($raw, true) ?? [])
        ) : [];

        if (empty($drafts)) {
            throw new \RuntimeException('Pending transaction has no parked postings to confirm.');
        }

        DB::transaction(function () use ($tx, $drafts) {
            $this->writePostings($tx, $drafts);

            $tx->forceFill([
                'status' => Transaction::STATUS_POSTED,
                'posted_at' => now(),
                'draft_postings' => null,
            ])->save();
        });

        App::make('events')->dispatch(new TransactionConfirmed($tx));
        App::make('events')->dispatch(new TransactionPosted($tx));

        return $tx;
    }

    /**
     * Void a pending transaction without ever writing its postings or moving balances.
     */
    public function void(Transaction $tx): Transaction
    {
        if (!$tx->isPending()) {
            throw new \RuntimeException(sprintf(
                'Only pending transactions can be voided; this one is %s.',
                $tx->getRawOriginal('status')
            ));
        }

        $tx->forceFill([
            'status' => Transaction::STATUS_VOIDED,
            'draft_postings' => null,
        ])->save();

        return $tx;
    }

    protected function makeTransaction(TransactionDraft $draft): Transaction
    {
        /** @var Transaction $tx */
        $tx = App::make(config('walletable.models.transaction'));

        $tx->forceFill(array_merge([
            'currency' => $draft->currency,
            'status' => $draft->status,
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
