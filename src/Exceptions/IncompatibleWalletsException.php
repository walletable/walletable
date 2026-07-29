<?php

namespace Walletable\Exceptions;

use Walletable\Models\Wallet;

class IncompatibleWalletsException extends WalletableException
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

    public function __construct(Wallet $wallet, Wallet $against)
    {
        parent::__construct('Can`t perform any operations between two incompatible wallets');

        $this->wallet = $wallet;
        $this->against = $against;
    }

    /**
     * Get wallet property
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * Get against property
     */
    public function getAgainst(): Wallet
    {
        return $this->against;
    }
}
