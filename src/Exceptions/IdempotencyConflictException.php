<?php

namespace Walletable\Exceptions;

use Walletable\Models\Transaction;

/**
 * Thrown when an idempotency key is reused for a materially different
 * transaction — a different amount, wallet, direction or reference.
 *
 * Returning the original transaction here would silently swallow the caller's
 * new intent, and posting a second one would defeat the key, so the only safe
 * answer is to refuse.
 */
class IdempotencyConflictException extends WalletableException
{
    /**
     * The transaction already stored under the key
     *
     * @var \Walletable\Models\Transaction
     */
    protected $existing;

    public function __construct(Transaction $existing, string $key)
    {
        parent::__construct(sprintf(
            'Idempotency key [%s] was already used for a different transaction [%s].',
            $key,
            $existing->getKey()
        ));

        $this->existing = $existing;
    }

    /**
     * The transaction that already holds the key
     */
    public function getExisting(): Transaction
    {
        return $this->existing;
    }
}
