<?php

namespace Walletable\Facades;

use Illuminate\Support\Facades\Facade;
use Walletable\WalletManager;

/**
 *
 */
class Wallet extends Facade
{
    protected static function getFacadeAccessor()
    {
        return WalletManager::class;
    }
}
