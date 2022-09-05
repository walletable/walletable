<?php

namespace Walletable\Tests\Mutation;

use Walletable\Internals\Mutation\AbstractMutation;

class TestMutationWrapperMethod extends AbstractMutation
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

    public function after()
    {
        $this->setValue($this->value . ')');
    }

    public function before()
    {
        $this->setValue('(' . $this->value);
    }
}
