<?php

namespace Walletable\Traits;

use Wallet;

trait Walletable
{
    /**
     * Generate a wallet for the model
     * @return string
     */
    public function createWallet(string $label, string $tag = null, string $currency = null, string $driver = null)
    {
        $driver = Wallet::instance()->{$driver ?? config('wallet.default')};

        $label = $label ?? $driver->getDefaultLabel();

        $currency = $currency ?? $driver->getDefaultCurrency();
        
        return Wallet::generate($name, $label, $currency, $driver, $this);
    }

    /**
     * Generate a wallet for the model
     * @return string
     */
    public function purse(string $driver = null, bool $catchError = false)
    {
        if (!$driver) {
            $driver = config('wallet.default');
        }

        try {
            $wallet = app(config('walletable.models.wallet'))->where('owner_id', $this->{$this->getKeyName()})->where('owner_type', static::class)->where('driver', $driver)->firstOrFail();
        } catch (\Throwable $th) {
            if ($catchError) {
                throw $th;
            }
            return false;
        }

        return Wallet::make($wallet);
    }
}
