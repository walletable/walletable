<?php

namespace Walletable\Facades;

use Illuminate\Support\Facades\Facade;
use Walletable\Internals\Mutation\MutatorManager;

/**
 * @method static \Walletable\Internals\Mutation\MutatorManager mutator(string $mutation, $mutators, $priority = self::PRIORITY_NORMAL)
 * @method static array getMutators(string $mutation)
 * @method static Walletable\Internals\Mutation\MutationInterface mutate(MutatiWalletable\Internals\Mutation\MutationInterfaceonInterface $mutation)
 * @method static array mutateBatch(array $mutations)
 * @method static array getMutators(string $mutation)
 */
class Mutator extends Facade
{
    protected static function getFacadeAccessor()
    {
        return MutatorManager::class;
    }
}
