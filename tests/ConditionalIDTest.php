<?php

namespace Walletable\Tests;

use PDO;
use Walletable\Tests\Models\Posting;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Walletable;

/**
 * Asserts that walletable's own foreign keys keep the configured key type. The
 * connection stringifies every fetched column here, the way PHP 8.0 and drivers
 * using emulated prepares do, so the casts are exercised on any runtime.
 */
class ConditionalIDTest extends TestBench
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set(
            'database.connections.' . $app['config']->get('database.default') . '.options',
            [PDO::ATTR_STRINGIFY_FETCHES => true]
        );

        parent::getEnvironmentSetUp($app);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testPostingKeysMatchTheModelsTheyReference()
    {
        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $entry = $wallet->transfer($wallet2, 50000, 'Test transfer');
        $debit = $entry->postings()->where('direction', 'D')->first();

        $this->assertSame($wallet->id, $debit->wallet_id);
        $this->assertSame($entry->id, $debit->transaction_id);
    }

    public function testKeyStrategyDrivesForeignKeyCasts()
    {
        foreach (['default' => 'int', 'uuid' => 'string', 'ulid' => 'string'] as $strategy => $type) {
            config()->set('walletable.model_id', $strategy);

            $this->assertSame($type, (new Posting())->getCasts()['wallet_id']);
            $this->assertSame($type, (new Posting())->getCasts()['transaction_id']);
            $this->assertSame($type, (new Transaction())->getCasts()['reverses_id']);
        }
    }
}
