<?php

namespace Walletable\Tests\Mutation;

use Walletable\Internals\Mutation\MutationInterface;
use Walletable\Internals\Mutation\MutatorInterface;

class TestMutatorWithExtra implements MutatorInterface
{
    /**
     * @inheritdoc
     */
    public function mutate(MutationInterface $mutation, $extra2 = null, $extra1 = null)
    {
        $mutation->setValue($mutation->value() . $extra1);
    }

    /**
     * @inheritdoc
     */
    public function isMutator($listener)
    {
        return $this === $listener;
    }
}
