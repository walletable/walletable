<?php

namespace Walletable\Exceptions;

use AssertionError;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

class IncompactibleWalletsException extends AssertionError
{
    /**
     * Wallet model
     *
     * @var \Walletable\Models\Wallet
     */
    protected $wallet;

    /**
     * The wallet you are checking
     *
     * @var \Walletable\Models\Wallet
     */
    protected $against;

    public function __construct(Wallet $wallet, Wallet $against) {
        $this->wallet = $wallet;
        $this->against = $against;
        $this->message = 'Can`t perform any operations between two incompactible wallets';
    }

    /**
     * Get wallet property
     *
     * @return string
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * Get against property
     *
     * @return string
     */
    public function getAgainst(): Wallet
    {
        return $this->against;
    }
}
