<?php

namespace Walletable\Ledger;

use Walletable\Models\Posting;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

/**
 * In-memory description of one posting before it becomes a Posting row.
 *
 * Mutable until PostTransaction serialises it. After save, the Posting
 * model takes over and rejects further changes.
 */
class PostingDraft
{
    public Wallet $wallet;
    public string $direction;
    public Money $amount;
    public string $action;
    public ?string $methodId = null;
    public ?string $methodType = null;
    public ?int $balanceAfter = null;
    public array $meta = [];

    public function __construct(
        Wallet $wallet,
        string $direction,
        Money $amount,
        string $action = 'credit_debit',
        ?string $methodId = null,
        ?string $methodType = null
    ) {
        if (!in_array($direction, [Posting::DIRECTION_DEBIT, Posting::DIRECTION_CREDIT], true)) {
            throw new \InvalidArgumentException(
                sprintf('Posting direction must be %s or %s', Posting::DIRECTION_DEBIT, Posting::DIRECTION_CREDIT)
            );
        }

        if ($amount->integer() <= 0) {
            throw new \InvalidArgumentException('Posting amount must be positive.');
        }

        $this->wallet = $wallet;
        $this->direction = $direction;
        $this->amount = $amount;
        $this->action = $action;
        $this->methodId = $methodId;
        $this->methodType = $methodType;
    }

    public function isCredit(): bool
    {
        return $this->direction === Posting::DIRECTION_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->direction === Posting::DIRECTION_DEBIT;
    }

    public function method(?string $type, ?string $id): self
    {
        $this->methodType = $type;
        $this->methodId = $id;
        return $this;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;
        return $this;
    }

    public function setMeta(string $key, $value): self
    {
        \data_set($this->meta, $key, $value, true);
        return $this;
    }

    public function getMeta(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->meta;
        }
        return \data_get($this->meta, $key, $default);
    }

    /**
     * Serialise to a portable array (used when stashing pending postings
     * inside Transaction.draft_postings for later confirmation).
     */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->wallet->getKey(),
            'direction' => $this->direction,
            'amount' => $this->amount->integer(),
            'currency' => $this->amount->getCurrency()->getCode(),
            'action' => $this->action,
            'method_id' => $this->methodId,
            'method_type' => $this->methodType,
            'meta' => $this->meta,
        ];
    }

    /**
     * Rehydrate a draft from its serialised form. The wallet is resolved
     * lazily via the configured Wallet model.
     */
    public static function fromArray(array $data): self
    {
        $walletClass = config('walletable.models.wallet');
        $wallet = $walletClass::query()->findOrFail($data['wallet_id']);

        $draft = new self(
            $wallet,
            $data['direction'],
            new \Walletable\Money\Money(
                $data['amount'],
                \Walletable\Money\Money::currency($data['currency'])
            ),
            $data['action'] ?? 'credit_debit',
            $data['method_id'] ?? null,
            $data['method_type'] ?? null
        );

        if (isset($data['meta']) && is_array($data['meta'])) {
            $draft->meta = $data['meta'];
        }

        return $draft;
    }
}
