<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

class CreatingTransaction
{
    use SerializesModels;

    /**
     * The created wallet.
     *
     * @var \Walletable\Models\Wallet
     */
    public $wallet;

    /**
     * The owner of the created wallet.
     *
     * @var \Walletable\Models\Transaction
     */
    public $transaction;

    /**
     * Create a new event instance.
     *
     * @param  \Walletable\Models\Wallet  $wallet
     * @param  \Walletable\Contracts\Walletable  $walletable
     *
     * @return void
     */
    public function __construct(Wallet $wallet, Transaction $transaction)
    {
        $this->wallet = $wallet;
        $this->transaction = $transaction;
    }
}
