<?php

namespace Walletable\Internals\Lockers;

use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

interface LockerInterface
{
    /**
     * Increase the balance of wallet model using a lock mechanism
     *
     * @param \Walletable\Models\Wallet $wallet
     * @param \Walletable\Money\Money $amount
     * @param \Walletable\Models\Transaction $transaction
     *
     * @return bool
     */
    public function creditLock(Wallet $wallet, Money $amount, Transaction $transaction);

    /**
     * Decrease the balance of wallet model using a lock machnism
     *
     * @param \Walletable\Models\Wallet $wallet
     * @param \Walletable\Money\Money $amount
     * @param \Walletable\Models\Transaction $transaction
     *
     * @return bool
     */
    public function debitLock(Wallet $wallet, Money $amount, Transaction $transaction);

    /**
     * Determine if database transaction should be initiated
     *
     * @param \Walletable\Models\Wallet $wallet
     * @param \Walletable\Money\Money $amount
     * @param \Walletable\Models\Transaction $transaction
     *
     * @return bool
     */
    public function shouldInitiateTransaction(Wallet $wallet, Money $amount, Transaction $transaction);
}
