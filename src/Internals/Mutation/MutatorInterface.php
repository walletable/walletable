<?php

namespace Walletable\Internals\Mutation;

interface MutatorInterface
{
    /**
     * Handle an Mutation.
     *
     * @param MutationInterface $Mutation
     *
     * @return void
     */
    public function mutate(MutationInterface $Mutation);

    /**
     * Check whether the mutator is the given parameter.
     *
     * @param mixed $mutator
     *
     * @return bool
     */
    public function isMutator($mutator);
}
