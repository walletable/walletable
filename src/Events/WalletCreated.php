<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;

class WalletCreated
{
    use SerializesModels;

    /**
     * The created wallet driver.
     *
     * @var \Walletable\Drivers\DriverInterface
     */
    public $driver;

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
     * @param  \Walletable\Drivers\DriverInterface  $driver
     * @param  \Walletable\Models\Wallet  $wallet
     * @param  \Walletable\Contracts\Walletable  $walletable
     *
     * @return void
     */
    public function __construct($driver, $wallet, $walletable)
    {
        $this->driver = $driver;
        $this->wallet = $wallet;
        $this->walletable = $walletable;
    }
}
