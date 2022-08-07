<?php

namespace Walletable;

use Illuminate\Support\ServiceProvider;
use Walletable\WalletableManager;
use Walletable\Commands\InstallCommand;
use Walletable\Facades\Walletable;
use Walletable\Internals\Lockers\OptimisticLocker;
use Walletable\Money\Formatter\IntlMoneyFormatter;
use Walletable\Money\Money;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\TransferAction;

class WalletableServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(WalletableManager::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Money::formatter('intl', function () {
            return new IntlMoneyFormatter(
                new \NumberFormatter('en_US', \NumberFormatter::CURRENCY)
            );
        });

        Walletable::locker('optimistic', OptimisticLocker::class);

        Walletable::action('transfer', TransferAction::class);
        Walletable::action('credit_debit', CreditDebitAction::class);

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
