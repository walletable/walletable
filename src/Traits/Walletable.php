<?php

namespace Walletable\Traits;
use Wallet;

trait Walletable
{
    /**
     * Generate a wallet for the model
     * @return string
     */
    public function createWallet(string $provider = null){
        if (!$provider) $provider = config('wallet.default');
        return Wallet::generate($provider, $this);
    }

    /**
     * Generate a wallet for the model
     * @return string
     */
    public function getWallet(string $provider = null, bool $catchError = false){

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