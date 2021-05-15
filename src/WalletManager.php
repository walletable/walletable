<?php

namespace Walletable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

class WalletManager
{
    private $app;

    private $providers;

    public function __construct( Application $app )
    {
        $this->app = $app;
        $this->registerBaseProviders();
    }

    public function make( Models\WalletInterface $wallet)
    {
        $providerKey = (property_exists($wallet, 'providerKey'))? $wallet->providerKey : 'provider';

        return $this->makeProvider($wallet->provider, $wallet);
    }

    public function makeProvider( string $provider, Models\WalletInterface $wallet) : Providers\ProviderInterface
    {
        return (isset($this->prividers[$provider]))? new $this->providers[$provider]($wallet) : new $this->providers[config('wallet.default')]($wallet);
    }

    public function registerBaseProviders()
    {
        $this->provider(Providers\DatabaseProvider::class);
        $this->provider(Providers\UnknownProvider::class);
        return $this;
    }

    public function provider(string $class)
    {
        if ( !(class_exists($class) && is_subclass_of($class, Providers\ProviderRepository::class)) ) throw new \Exception("Invalid Provider class [$class]");
        
        $this->providers[$class::signature()] = $class;
    }

    public function generateForModel( string $label, string $tag, string $currency, Contracts\ProviderInterface $provider, Contracts\Walletable $walletable)
    {
        $owner_id = $owner->{$walletable->getKeyName()};
        $owner_type = get_class($walletable);
        $wallet = app(config('walletable.models.wallet'))->fill(
            [
                'walletable_id' => $owner_id,
                'walletable_type' => $owner_type,
                'label' => $label,
                'name' => $name,
                'provider' => $provider->signature(),
                'balance' => 0,
                'data' => '{}',
            ]
        );

        $i = 1;
        while ($i <= config('wallet.generation.tries', 5)) {

            $result = $providerClass::generate( $wallet, $walletable);

            if ($result['success']) {
                break;
            }

        }

        if ($result['success']) {
            $wallet->fill(
                $result['data']
            )->save();
            return $this->make($wallet);
        }else{
            return false;
        }

    }


}
