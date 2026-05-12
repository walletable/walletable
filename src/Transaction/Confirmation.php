<?php

namespace Walletable\Transaction;

use Walletable\Ledger\PostTransaction;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

/**
 * Confirm a pending transaction: writes the parked postings and applies the
 * balance changes. Delegates to PostTransaction::confirm.
 */
class Confirmation
{
    protected Wallet $wallet;
    protected Transaction $transaction;

    public function __construct(Wallet $wallet, Transaction $transaction)
    {
        $this->wallet = $wallet;
        $this->transaction = $transaction;
    }

    public function execute(): Transaction
    {
        return (new PostTransaction())->confirm($this->transaction);
    }
}
