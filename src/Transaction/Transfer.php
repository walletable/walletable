<?php

namespace Walletable\Transaction;

use Walletable\Exceptions\IncompactibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
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

    public function __construct(Wallet $sender, Money $amount, Wallet $receiver, ?string $remarks = null)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->amount = $amount;
        $this->remarks = $remarks;
    }

    public function execute(): Transaction
    {
        if ($this->sender->amount->lessThan($this->amount)) {
            throw new InsufficientBalanceException($this->sender, $this->amount);
        }

        if (!$this->sender->compactible($this->receiver)) {
            throw new IncompactibleWalletsException($this->sender, $this->receiver);
        }

        $draft = TransactionDraft::transfer(
            $this->sender,
            $this->receiver,
            $this->amount,
            'transfer',
            $this->remarks
        );

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
