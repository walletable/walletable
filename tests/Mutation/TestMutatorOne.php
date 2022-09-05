<?php

namespace Walletable\Tests\Mutation;

use Walletable\Internals\Mutation\MutationInterface;
use Walletable\Internals\Mutation\MutatorInterface;

class TestMutatorOne implements MutatorInterface
{
    /**
     * @inheritdoc
     */
    public function mutate(MutationInterface $mutation)
    {
        $mutation->setValue($mutation->value() . ' one');
    }

    /**
     * @inheritdoc
     */
    public function isMutator($listener)
    {
        return $this === $listener;
    }
}
