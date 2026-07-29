<?php

namespace Walletable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Walletable\Models\HouseAccount;
use Walletable\Money\Currency;
use Walletable\Money\Money;
use Walletable\Tests\Models\Posting;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Wallet;
use Walletable\Tests\Models\Walletable;
use Walletable\WalletableServiceProvider;

class TestBench extends BaseTestCase
{
    use MockeryPHPUnitIntegration;

    protected function getEnvironmentSetUp($app)
    {
        $config = require __DIR__ . '/../config/walletable.php';

        $app['config']->set('walletable', $config);
        $app['config']->set('walletable.models.wallet', Wallet::class);
        $app['config']->set('walletable.models.transaction', Transaction::class);
        $app['config']->set('walletable.models.posting', Posting::class);
        $app['config']->set('walletable.models.house_account', HouseAccount::class);
        $this->migrate();
    }

    protected function getPackageProviders($app)
    {
        return [
            WalletableServiceProvider::class,
        ];
    }

    public function setUpCurrencies()
    {
        Money::currencies(
            Currency::new('NGN', '₦', 'Naira', 'Kobo', 100, 566),
            Currency::new('USD', '$', 'Dollar', 'Cent', 100, 840)
        );
    }

    public function migrate()
    {
        include_once __DIR__ . '/../database/migrations/2020_12_25_001500_create_wallets_table.php';
        include_once __DIR__ . '/../database/migrations/2020_12_25_001550_create_house_accounts_table.php';
        include_once __DIR__ . '/../database/migrations/2020_12_25_001600_create_transactions_table.php';
        include_once __DIR__ . '/../database/migrations/2020_12_25_001700_create_postings_table.php';

        (new \CreateWalletsTable())->up();
        (new \CreateHouseAccountsTable())->up();
        (new \CreateTransactionsTable())->up();
        (new \CreatePostingsTable())->up();

        Schema::create('walletables', function (Blueprint $table) {
            $table->id();
            $table->string('name', 75);
            $table->string('email', 75);
            $table->timestamps();
        });
    }

    public function createWallet(int $amount = 0, string $currency = 'NGN', Walletable $walletable = null): Wallet
    {
        $walletable = $walletable ?? Walletable::create([
            'name' => 'Olawale Ilesanmi',
            'email' => 'olawale@olawale.com',
        ]);

        return $walletable->wallets()->create([
            'label' => 'Main Wallet',
            'tag' => 'main',
            'amount' => $amount,
            'currency' => $currency,
            'meta' => '[]',
            'status' => 'active',
        ])->setRelation('walletable', $walletable);
    }
}
