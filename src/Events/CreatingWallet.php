<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;

class CreatingWallet
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
     * @var \Walletable\Contracts\Walletable
     */
    public $walletable;

    /**
     * Create a new event instance.
     *
     * @param  \Walletable\Models\Wallet  $wallet
     * @param  \Walletable\Contracts\Walletable  $walletable
     *
     * @return void
     */
    public function __construct($wallet, $walletable)
    {
        $this->wallet = $wallet;
        $this->walletable = $walletable;
    }
}
