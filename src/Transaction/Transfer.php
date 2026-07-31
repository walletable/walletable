<?php

namespace Walletable\Transaction;

use Walletable\Exceptions\IncompatibleWalletsException;
use Walletable\Facades\Walletable;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\AppliesToTransaction;
use Walletable\Ledger\PostTransaction;
use Walletable\Ledger\TransactionDraft;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

/**
 * Two-leg transfer between user wallets. Posts a balanced transaction with
 * a debit on the sender and a credit on the receiver; the house account is
 * not involved.
 */
class Transfer
{
    protected Wallet $sender;
    protected Wallet $receiver;
    protected Money $amount;
    protected ?string $remarks;
    protected ?string $idempotencyKey = null;

    public function __construct(Wallet $sender, Money $amount, Wallet $receiver, ?string $remarks = null)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->amount = $amount;
        $this->remarks = $remarks;
    }

    /**
     * De-duplicate this transfer under the given key: a repeat call returns the
     * transaction posted by the first one instead of moving money again.
     */
    public function idempotent(?string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    public function execute(): Transaction
    {
        if (!$this->sender->compatible($this->receiver)) {
            throw new IncompatibleWalletsException($this->sender, $this->receiver);
        }

        $draft = TransactionDraft::transfer(
            $this->sender,
            $this->receiver,
            $this->amount,
            'transfer',
            $this->remarks
        )->idempotent($this->idempotencyKey);

        $action = Walletable::action('transfer');

        $data = new ActionData($this->sender, $this->receiver);

        // Both legs get the same action; apply() decorates each with the
        // opposite wallet's owner as the "method".
        $action->apply($draft->postings[0], $data);
        $action->apply($draft->postings[1], $data);

        if ($action instanceof AppliesToTransaction) {
            $action->applyToTransaction($draft, $data);
        }

        return (new PostTransaction())->execute($draft);
    }
}
