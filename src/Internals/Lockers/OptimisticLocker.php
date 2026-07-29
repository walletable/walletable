<?php

namespace Walletable\Internals\Lockers;

use Walletable\Exceptions\ConcurrencyException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Models\Posting;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

/**
 * Compare-and-swap balance update on the wallets row. The CAS uses the
 * wallet's last-seen amount as a guard; a contended writer loops with a
 * refreshed read until it wins.
 *
 * Retries are bounded and backed off with full jitter, so a hot wallet
 * surfaces a ConcurrencyException instead of livelocking the worker.
 */
class OptimisticLocker implements LockerInterface
{
    public const MAX_ATTEMPTS = 8;
    public const INITIAL_DELAY_MS = 1;
    public const MAX_DELAY_MS = 50;

    protected int $maxAttempts;
    protected int $initialDelayMs;
    protected int $maxDelayMs;

    public function __construct(
        int $maxAttempts = self::MAX_ATTEMPTS,
        int $initialDelayMs = self::INITIAL_DELAY_MS,
        int $maxDelayMs = self::MAX_DELAY_MS
    ) {
        $this->maxAttempts = max(1, $maxAttempts);
        $this->initialDelayMs = max(0, $initialDelayMs);
        $this->maxDelayMs = max($this->initialDelayMs, $maxDelayMs);
    }

    public function apply(
        Wallet $wallet,
        string $direction,
        Money $amount,
        bool $allowNegative = false
    ): int {
        $attempts = 0;
        $delay = $this->initialDelayMs;

        while (true) {
            $attempts++;

            $wallet->refresh();
            $current = $wallet->amount;
            $newBalance = $direction === Posting::DIRECTION_CREDIT
                ? $current->add($amount)
                : $current->subtract($amount);

            if (!$allowNegative && $newBalance->integer() < 0) {
                throw new InsufficientBalanceException($wallet, $amount);
            }

            if ($this->compareAndSwap($wallet, $current->value(), $newBalance->integer())) {
                $wallet->amount = $newBalance->integer();

                return $newBalance->integer();
            }

            if ($attempts >= $this->maxAttempts) {
                throw new ConcurrencyException($wallet, $attempts);
            }

            $this->backOff($delay);
            $delay = min($delay * 2, $this->maxDelayMs);
        }
    }

    /**
     * Swap the wallet balance only if it still holds the value we read.
     * Returns false when another writer got there first.
     */
    protected function compareAndSwap(Wallet $wallet, $expected, int $new): bool
    {
        return (bool) config('walletable.models.wallet')::whereId($wallet->getKey())
            ->whereAmount($expected)
            ->update(['amount' => $new]);
    }

    /**
     * Full jitter: sleep for a random slice of the current backoff window so
     * competing writers spread out instead of colliding again in lockstep.
     */
    protected function backOff(int $delayMs): void
    {
        if ($delayMs > 0) {
            usleep(random_int(0, $delayMs * 1000));
        }
    }
}
