<?php

namespace Walletable\Facades;

use Illuminate\Support\Facades\Facade;
use Walletable\WalletManager;

/**
 * @method \Walletable\Models\Wallet create(\Walletable\Contracts\Walletable $walletable, string $label, string $tag, string $currency)
 * @method bool compactible(Wallet $wallet, Wallet $against)
 * @method mixed compactible(Wallet $wallet, Wallet $against)
 * @method void macro($name, $macro)
 * @method void flushMacros()
 * @method bool hasMacro($name)
 * @method void mixin($mixin, $replace = true)
 * @method \Walletable\Internals\Lockers\LockerInterface|void locker(string $name, $locker = null)
 * @method \Walletable\Internals\Actions\ActionInterface|void action(string $name, $action = null)
 */
class Wallet extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WalletManager::class;
    }
}
