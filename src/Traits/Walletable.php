<?php

namespace Walletable\Traits;
use Wallet;

trait Walletable
{
    /**
     * Generate a wallet for the model
     * @return string
     */
    public function createWallet(string $label, string $tag = null, string $currency = null, string $provider = null){

        $provider = Wallet::instance()->{$provider ?? config('wallet.default')};

        $label = $label ?? $provider->getDefaultLabel();

        $currency = $currency ?? $provider->getDefaultCurrency();
        
        return Wallet::generate( $name, $label, $currency, $provider, $this);
    }

    /**
     * Generate a wallet for the model
     * @return string
     */
    public function purse(string $provider = null, bool $catchError = false){

        if (!$provider) $provider = config('wallet.default');

        try {

            $wallet = app(config('walletable.models.wallet'))->where('owner_id', $this->{$this->getKeyName()})->where('owner_type', static::class)->where('provider', $provider)->firstOrFail();
            
        } catch (\Throwable $th) {

            if ($catchError) throw $th;
            return false;

        }

        return Wallet::make($wallet);
    }
}