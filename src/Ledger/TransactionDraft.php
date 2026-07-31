<?php

namespace Walletable\Ledger;

use Walletable\Models\Posting;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

/**
 * Builder for a transaction plus its postings, prior to PostTransaction
 * persisting them. Holds enough information to either commit immediately
 * (status = posted) or park as pending for later confirmation.
 */
class TransactionDraft
{
    public string $currency;
    public string $status = Transaction::STATUS_POSTED;
    public ?string $narration = null;
    public ?string $referenceType = null;
    public ?string $referenceId = null;
    public ?array $meta = null;

    /**
     * Caller-supplied de-duplication key. When set, PostTransaction returns the
     * transaction already written under this key instead of posting a second one.
     */
    public ?string $idempotencyKey = null;

    /**
     * Extra top-level columns for the transaction row, keyed by column name.
     * Held as-is: the application declares which columns are legal through
     * Walletable::extendTransaction(), and PostTransaction enforces it.
     *
     * @var array<string,mixed>
     */
    public array $attributes = [];

    /** @var PostingDraft[] */
    public array $postings = [];

    public function __construct(string $currency)
    {
        $this->currency = $currency;
    }

    /** Single-leg credit or debit against a user wallet, mirrored by the house wallet. */
    public static function singleLeg(
        Wallet $userWallet,
        Wallet $houseWallet,
        string $direction,
        Money $amount,
        string $action = 'credit_debit',
        ?string $narration = null
    ): self {
        if ($userWallet->getRawOriginal('currency') !== $houseWallet->getRawOriginal('currency')) {
            throw new \InvalidArgumentException('User and house wallet currencies must match.');
        }

        $opposite = $direction === Posting::DIRECTION_CREDIT
            ? Posting::DIRECTION_DEBIT
            : Posting::DIRECTION_CREDIT;

        $draft = new self($userWallet->getRawOriginal('currency'));
        $draft->narration = $narration;
        $draft->postings = [
            new PostingDraft($userWallet, $direction, $amount, $action),
            new PostingDraft($houseWallet, $opposite, $amount, $action),
        ];
        return $draft;
    }

    /** Wallet-to-wallet transfer; house account is untouched. */
    public static function transfer(
        Wallet $sender,
        Wallet $receiver,
        Money $amount,
        string $action = 'transfer',
        ?string $narration = null
    ): self {
        if ($sender->getRawOriginal('currency') !== $receiver->getRawOriginal('currency')) {
            throw new \InvalidArgumentException('Transfer wallets must share a currency.');
        }

        $draft = new self($sender->getRawOriginal('currency'));
        $draft->narration = $narration;
        $draft->postings = [
            new PostingDraft($sender, Posting::DIRECTION_DEBIT, $amount, $action),
            new PostingDraft($receiver, Posting::DIRECTION_CREDIT, $amount, $action),
        ];
        return $draft;
    }

    public function pending(): self
    {
        $this->status = Transaction::STATUS_PENDING;
        return $this;
    }

    public function withMeta(?array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function withReference(?string $type, ?string $id): self
    {
        $this->referenceType = $type;
        $this->referenceId = $id;
        return $this;
    }

    /**
     * De-duplicate this draft under the given key. Passing null or an empty
     * string leaves the draft non-idempotent.
     */
    public function idempotent(?string $key): self
    {
        $this->idempotencyKey = ($key === null || $key === '') ? null : $key;
        return $this;
    }

    /**
     * Digest of everything that makes this draft financially distinct: the
     * currency, whether it posts immediately, its external reference, the
     * application-declared columns, and each leg's wallet, direction, amount,
     * action and method.
     *
     * Take the fingerprint after the action has decorated the draft, so that
     * whatever the ActionData resolved to — the counterparty on each leg, the
     * staged columns — is covered. Reusing a key with a different counterparty
     * is a conflict, not a replay.
     *
     * Narration and meta are deliberately excluded — they vary between retries
     * of the same intent and are not worth rejecting a replay over.
     *
     * @throws \RuntimeException when a staged value cannot be encoded, and so
     *                           cannot be told apart from any other such value.
     */
    public function fingerprint(): string
    {
        $attributes = $this->attributes;
        ksort($attributes);

        try {
            $payload = json_encode([
                'currency' => $this->currency,
                'status' => $this->status,
                'reference_type' => $this->referenceType,
                'reference_id' => $this->referenceId,
                'attributes' => $attributes,
                'postings' => array_map(fn(PostingDraft $p) => [
                    'wallet_id' => (string) $p->wallet->getKey(),
                    'direction' => $p->direction,
                    'amount' => $p->amount->integer(),
                    'currency' => $p->amount->getCurrency()->getCode(),
                    'action' => $p->action,
                    'method_type' => $p->methodType,
                    'method_id' => $p->methodId,
                ], $this->postings),
            ], JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            // Every unencodable payload would otherwise digest to the same
            // value, and two drafts sharing a fingerprint replay as one another.
            throw new \RuntimeException(
                'Cannot fingerprint this transaction for idempotency: a staged value is not ' .
                'JSON-encodable. Store an encodable representation, or drop the idempotency key.',
                0,
                $e
            );
        }

        return hash('sha256', $payload);
    }

    /**
     * Stage an application-defined column for the transaction row.
     */
    public function set(string $column, $value): self
    {
        $this->attributes[$column] = $value;
        return $this;
    }

    /**
     * @param array<string,mixed> $attributes
     */
    public function setMany(array $attributes): self
    {
        foreach ($attributes as $column => $value) {
            $this->set($column, $value);
        }
        return $this;
    }

    public function get(string $column, $default = null)
    {
        return $this->attributes[$column] ?? $default;
    }

    /** @return PostingDraft[] */
    public function postings(): array
    {
        return $this->postings;
    }

    /**
     * Assert that debits and credits sum to zero and currencies match.
     *
     * @throws \RuntimeException when the draft is not balanced or has a currency mismatch.
     */
    public function assertBalanced(): void
    {
        if (count($this->postings) < 2) {
            throw new \RuntimeException('A transaction must contain at least two postings.');
        }

        $debit = 0;
        $credit = 0;
        foreach ($this->postings as $p) {
            if ($p->amount->getCurrency()->getCode() !== $this->currency) {
                throw new \RuntimeException(sprintf(
                    'Posting currency %s does not match transaction currency %s.',
                    $p->amount->getCurrency()->getCode(),
                    $this->currency
                ));
            }

            if ($p->isCredit()) {
                $credit += $p->amount->integer();
            } else {
                $debit += $p->amount->integer();
            }
        }

        if ($debit !== $credit) {
            throw new \RuntimeException(sprintf(
                'Transaction is not balanced: debits=%d credits=%d.',
                $debit,
                $credit
            ));
        }
    }

    /**
     * Serialise the postings for pending storage on the transaction.
     */
    public function postingsToArray(): array
    {
        return array_map(fn(PostingDraft $p) => $p->toArray(), $this->postings);
    }
}
