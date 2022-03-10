<?php

namespace Walletable\Contracts;

use Walletable\Internals\Argument;

interface ArgumentBag
{
    /**
     * Get the argument instance
     *
     * @param int $key
     */
    public function argument(int $key = 0): Argument;

    /**
     * Get key
     *
     * @param int $key
     */
    public function getKeyValue(int $key);

    /**
     * Check if key exists
     *
     * @param int $key
     * @return bool
     */
    public function keyExists(int $key): bool;

    /**
     * Get the name of the bag
     *
     * @return string
     */
    public function getName(): string;
}
