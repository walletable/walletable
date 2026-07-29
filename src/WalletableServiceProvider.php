<?php

namespace Walletable;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Walletable\Commands\InstallCommand;
use Walletable\Commands\ReconcileCommand;
use Walletable\Commands\SyncHouseAccountsCommand;
use Walletable\Facades\Walletable;
use Walletable\Internals\Lockers\OptimisticLocker;
use Walletable\Internals\Mutation\MutatorManager;
use Walletable\Models\HouseAccount;
use Walletable\Money\Formatter\IntlMoneyFormatter;
use Walletable\Money\Money;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\TransferAction;

class WalletableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(WalletableManager::class);
        $this->app->singleton(MutatorManager::class);
    }

    public function boot()
    {
        Money::formatter('intl', function () {
            return new IntlMoneyFormatter(
                new \NumberFormatter('en_US', \NumberFormatter::CURRENCY)
            );
        });

        Relation::morphMap([
            'house_account' => HouseAccount::class,
        ]);

        Walletable::locker('optimistic', OptimisticLocker::class);

        Walletable::action('transfer', TransferAction::class);
        Walletable::action('credit_debit', CreditDebitAction::class);

        $this->addPublishes();
        $this->addCommands();
    }

    public function addPublishes()
    {
        $this->publishes([
            __DIR__ . '/../config/walletable.php' => config_path('walletable.php'),
        ], 'walletable.config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'walletable.migrations');

        $this->publishes([
            __DIR__ . '/../database/models' => app_path('Models'),
        ], 'walletable.models');
    }

    protected function addCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                ReconcileCommand::class,
                SyncHouseAccountsCommand::class,
            ]);
        }
    }
}
