<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;
use Walletable\Models\Transaction;

/**
 * Fired when a pending transaction is confirmed (transitions to posted).
 * Listeners interested only in posted transactions can also subscribe to
 * TransactionPosted, which is dispatched together with this event.
 */
class TransactionConfirmed
{
    use SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
