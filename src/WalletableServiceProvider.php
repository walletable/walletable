<?php

namespace Walletable;

use Illuminate\Support\ServiceProvider;
use Walletable\WalletManager;
use Walletable\Commands\InstallCommand;
use Walletable\Drivers\DatabaseDriver;
use Walletable\Facades\Wallet;

class WalletableServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WalletManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Wallet::driver('database', DatabaseDriver::class);

        $this->addPublishes();
        $this->addCommands();
    }

    /**
     * Register Walletable's publishable files.
     *
     * @return void
     */
    public function addPublishes()
    {
        $this->publishes([
            __DIR__ . '/../config/walletable.php' => config_path('walletable.php')
        ], 'walletable.config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'walletable.migrations');

        $this->publishes([
            __DIR__ . '/../database/models' => app_path('Models'),
        ], 'walletable.models');
    }

    /**
     * Register Walletable's commands.
     *
     * @return void
     */
    protected function addCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
        }
    }
}
