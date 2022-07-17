<?php

namespace Walletable\Facades;

use Illuminate\Support\Facades\Facade;
use Walletable\WalletManager;

/**
 * @method static \Walletable\Models\Wallet create(\Walletable\Contracts\Walletable $walletable, string $label, string $tag, string $currency)
 * @method static bool compactible(Wallet $wallet, Wallet $against)
 * @method static mixed compactible(Wallet $wallet, Wallet $against)
 * @method static void macro($name, $macro)
 * @method static void flushMacros()
 * @method static bool hasMacro($name)
 * @method static void mixin($mixin, $replace = true)
 * @method static \Walletable\Internals\Lockers\LockerInterface|void locker(string $name, $locker = null)
 * @method static \Walletable\Internals\Actions\ActionInterface|void action(string $name, $action = null)
 */
class Wallet extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WalletManager::class;
    }
}
