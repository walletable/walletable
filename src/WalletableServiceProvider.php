<?php

namespace Walletable\Walletable;

use Illuminate\Support\ServiceProvider;
use Walletable\Walletable\WalletManager;
use Walletable\Walletable\Commands\InstallCommand;

class WalletableServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton( WalletManager::class, function (){

            return new WalletManager;

        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        $this->addPublishes();

        $this->addCommands();

    }


    public function addPublishes()
    {

        $this->publishes([

            __DIR__.'/../config/walletable.php' => config_path('walletable.php')

        ], 'walletable.config');

    }


    protected function addCommands()
    {
        if ($this->app->runningInConsole()) {

            $this->commands([

                InstallCommand::class,

            ]);

        }
    }


}
