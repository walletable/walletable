<?php

namespace Walletable\Exceptions;

use Walletable\Models\Wallet;
use Walletable\Money\Money;

class InsufficientBalanceException extends WalletableException
{
    /**
     * Wallet model
     *
     * @var \Walletable\Models\Wallet
     */
    protected $wallet;

    /**
     * Money object of amount that should be deducted
     *
     * @var \Walletable\Money\Money
     */
    protected $amount;

    public function __construct(Wallet $wallet, Money $amount)
    {
        parent::__construct('Insufficient wallet balance, The wallet balance is less than ' . $amount);

        $this->wallet = $wallet;
        $this->amount = $amount;
    }

    /**
     * Get wallet property
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * Get amount property
     */
    public function getAmount(): Money
    {
        return $this->amount;
    }
}
