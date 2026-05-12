<?php

namespace Walletable\Internals\Lockers;

use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Models\Posting;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

/**
 * Compare-and-swap balance update on the wallets row. The CAS uses the
 * wallet's last-seen amount as a guard; a contended writer loops with a
 * refreshed read until it wins.
 *
 * Note: retry is unbounded here (P1-1 follow-up). Concurrency-correct
 * but not yet hardened against starvation.
 */
class OptimisticLocker implements LockerInterface
{
    public function apply(
        Wallet $wallet,
        string $direction,
        Money $amount,
        bool $allowNegative = false
    ): int {
        $updated = false;
        $newBalance = null;

        do {
            $wallet->refresh();
            $current = $wallet->amount;
            $newBalance = $direction === Posting::DIRECTION_CREDIT
                ? $current->add($amount)
                : $current->subtract($amount);

            if (!$allowNegative && $newBalance->integer() < 0) {
                throw new InsufficientBalanceException($wallet, $amount);
            }

            $updated = config('walletable.models.wallet')::whereId($wallet->getKey())
                ->whereAmount($current->value())
                ->update(['amount' => $newBalance->integer()]);
        } while (!$updated);

        $wallet->amount = $newBalance->integer();

        return $newBalance->integer();
    }
}
