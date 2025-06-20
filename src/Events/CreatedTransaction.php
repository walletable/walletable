<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;
use Walletable\Models\Transaction;

class CreatedTransaction
{
    use SerializesModels;


    /**
     * The owner of the created wallet.
     *
     * @var \Walletable\Models\Transaction
     */
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @param  \Walletable\Models\Transaction  $transaction
     *
     * @return void
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
