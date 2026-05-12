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
