<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;
use Walletable\Models\Transaction;

/**
 * Fired when a two-phase transaction is parked in the pending state with
 * its postings stashed for later confirmation. Wallet balances are unchanged.
 */
class TransactionPending
{
    use SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
