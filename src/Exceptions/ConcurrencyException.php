<?php

namespace Walletable\Exceptions;

use Walletable\Models\Wallet;

/**
 * Thrown when a locker cannot win its balance update within the allowed
 * number of attempts — i.e. the wallet is hot enough that this writer is
 * being starved. Callers should retry the whole operation or route the
 * wallet to a locker better suited to contention.
 */
class ConcurrencyException extends WalletableException
{
    /**
     * Wallet model
     *
     * @var \Walletable\Models\Wallet
     */
    protected $wallet;

    /**
     * Number of attempts made before giving up
     *
     * @var int
     */
    protected $attempts;

    public function __construct(Wallet $wallet, int $attempts)
    {
        parent::__construct(sprintf(
            'Could not apply a balance update to wallet [%s] after %d attempts; the wallet is too contended.',
            $wallet->getKey(),
            $attempts
        ));

        $this->wallet = $wallet;
        $this->attempts = $attempts;
    }

    /**
     * Get wallet property
     */
    public function getWallet(): Wallet
    {
        return $this->wallet;
    }

    /**
     * Number of attempts made before giving up
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }
}
