<?php

namespace Walletable\Internals\Actions;

use Walletable\Contracts\ArgumentBag;
use Walletable\Internals\Argument;

class ActionData implements ArgumentBag
{
    /**
     * Arguments
     */
    protected $arguments;

    public function __construct(...$arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * Get the argument instance
     *
     * @param int $key
     */
    public function argument(int $key = 0): Argument
    {
        return new Argument($this, $key);
    }

    /**
     * Get key
     *
     * @param int $key
     */
    public function getKeyValue(int $key)
    {
        return isset($this->arguments[$key]) ? $this->arguments[$key] : null;
    }

    /**
     * Check if key exists
     *
     * @param int $key
     * @return bool
     */
    public function keyExists(int $key): bool
    {
        return isset($this->arguments[$key]);
    }

    /**
     * Get the name of the bag
     *
     * @return string
     */
    public function getName(): string
    {
        return 'ActionData';
    }
}
