<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Money\Currencies\ISOCurrencies;
use Money\Formatter\IntlMoneyFormatter;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Schema\Blueprint;
use ManeOlawale\Walletable\WalletRepository;

class WalletableServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->singleton( WalletRepository::class, function (){

            return new WalletRepository;

        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

    }


}
