<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;
use Walletable\Models\Transaction;

/**
 * Fired after a transaction transitions to (or is created in) the posted
 * state and all of its postings have been written.
 */
class TransactionPosted
{
    use SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
