<?php

namespace Walletable\Tests\Mutation;

use Walletable\Internals\Mutation\AbstractMutation;

class TestMutation extends AbstractMutation
{
    /**
     * Create a new named mustation
     *
     * @param string $name
     */
    public function __construct($value, array $extras = [])
    {
        $this->value = $value;
        $this->extras = $extras;
    }
}
