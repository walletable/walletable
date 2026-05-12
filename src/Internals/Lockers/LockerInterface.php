<?php

namespace Walletable\Internals\Lockers;

use Walletable\Models\Wallet;
use Walletable\Money\Money;

interface LockerInterface
{
    /**
     * Apply a posting amount to a wallet's materialised balance and return
     * the new balance. Implementations must guarantee that concurrent
     * applications stay correct (no lost updates).
     *
     * @param Wallet $wallet         The wallet whose balance changes.
     * @param string $direction      'C' for credit (add) or 'D' for debit (subtract).
     * @param Money  $amount         A positive amount in the wallet's currency.
     * @param bool   $allowNegative  When false, a debit that would push the
     *                               balance below zero must throw
     *                               InsufficientBalanceException. House
     *                               accounts pass true.
     *
     * @return int The new signed balance after the application.
     */
    public function apply(
        Wallet $wallet,
        string $direction,
        Money $amount,
        bool $allowNegative = false
    ): int;
}
