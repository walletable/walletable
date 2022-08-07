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
     * @param bool $confirmed
     * @param \Walletable\Models\Transaction $transaction
     *
     * @return bool
     */
    public function creditLock(Wallet $wallet, Money $amount, bool $confirmed, Transaction $transaction);

    /**
     * Decrease the balance of wallet model using a lockmachnism
     *
     * @param \Walletable\Models\Wallet $wallet
     * @param \Walletable\Money\Money $amount
     * @param bool $confirmed
     * @param \Walletable\Models\Transaction $transaction
     *
     * @return bool
     */
    public function debitLock(Wallet $wallet, Money $amount, bool $confirmed, Transaction $transaction);
}
