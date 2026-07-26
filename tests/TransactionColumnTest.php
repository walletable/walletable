<?php

namespace Walletable\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Walletable\Events\TransactionCreating;
use Walletable\Exceptions\InvalidArgumentException;
use Walletable\Facades\Walletable as WalletableFacade;
use Walletable\Internals\Actions\Action;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\AppliesToTransaction;
use Walletable\Ledger\LedgerImmutableException;
use Walletable\Ledger\TransactionDraft;
use Walletable\Ledger\UnregisteredTransactionColumnException;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Walletable as WalletableModel;
use Walletable\Transaction\TransferAction;

/**
 * Application-declared transaction columns: the package supplies the plumbing,
 * the application owns the migration and the allow-list.
 */
class TransactionColumnTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();

        // Stands in for a migration in the consuming application.
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('channel', 45)->nullable();
        });
    }

    public function testActionPopulatesDeclaredColumn()
    {
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        $entry = $action->credit(5000, new ActionData($wallet, 'mobile'), 'Topup');

        $this->assertSame('mobile', $entry->refresh()->channel);
        $this->assertSame('Topup', $entry->narration);
    }

    public function testActionFallsBackToItsOwnDefault()
    {
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        $entry = $action->credit(5000, new ActionData($wallet));

        $this->assertSame('web', $entry->refresh()->channel);
    }

    public function testUndeclaredColumnFailsAtomically()
    {
        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        try {
            $action->credit(5000, new ActionData($wallet, 'mobile'), 'Topup');
            $this->fail('Expected an ' . UnregisteredTransactionColumnException::class);
        } catch (UnregisteredTransactionColumnException $e) {
            $this->assertStringContainsString('channel', $e->getMessage());
        }

        // Nothing may leak: no transaction row, no balance movement.
        $this->assertSame(0, Transaction::count());
        $this->assertSame(0, $wallet->refresh()->amount->integer());
    }

    public function testReservedColumnCannotBeDeclared()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Column "status" is managed by Walletable and cannot be extended.');

        WalletableFacade::extendTransaction(['status']);
    }

    public function testDeclaredColumnSurvivesConfirmation()
    {
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $action = new Action($wallet, new TestColumnAction());

        $pending = $action->unconfirmedCredit(2000, new ActionData($wallet, 'ussd'), 'Pending topup');

        $this->assertSame('pending', $pending->status);
        $this->assertSame('ussd', $pending->refresh()->channel);

        $confirmed = $wallet->confirm($pending->refresh());

        $this->assertSame('posted', $confirmed->status);
        $this->assertSame('ussd', $confirmed->refresh()->channel);
    }

    public function testTransferActionCanPopulateDeclaredColumn()
    {
        WalletableFacade::extendTransaction(['channel']);
        WalletableFacade::action('transfer', function () {
            return new class () extends TransferAction implements AppliesToTransaction {
                public function applyToTransaction(TransactionDraft $draft, ActionData $data): void
                {
                    $draft->set('channel', 'wallet_transfer');
                }
            };
        });

        $sender = $this->createWallet(10000);
        $receiver = $this->createWallet(0, 'NGN', WalletableModel::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $entry = $sender->transfer($receiver, 4000, 'Test transfer');

        $this->assertSame('wallet_transfer', $entry->refresh()->channel);
    }

    public function testListenerCanStampDeclaredColumnWithoutACustomAction()
    {
        WalletableFacade::extendTransaction(['channel']);

        Event::listen(TransactionCreating::class, function (TransactionCreating $event) {
            $event->draft->set('channel', 'api');
        });

        $wallet = $this->createWallet(0);
        $entry = $wallet->credit(1500, 'Topup');

        $this->assertSame('api', $entry->refresh()->channel);
    }

    public function testDraftItselfDoesNotEnforceTheAllowList()
    {
        $draft = new TransactionDraft('NGN');
        $draft->set('anything_at_all', 'value')->setMany(['branch_id' => 12]);

        $this->assertSame('value', $draft->get('anything_at_all'));
        $this->assertSame(12, $draft->get('branch_id'));
        $this->assertNull($draft->get('missing'));
        $this->assertFalse(WalletableFacade::allowsTransactionColumn('anything_at_all'));
    }

    public function testRegistryIsAdditiveAndFlushable()
    {
        WalletableFacade::extendTransaction('channel');
        WalletableFacade::extendTransaction(['channel', 'branch_id']);

        $this->assertSame(['channel', 'branch_id'], WalletableFacade::transactionColumns());
        $this->assertTrue(WalletableFacade::allowsTransactionColumn('branch_id'));

        WalletableFacade::flushTransactionColumns();

        $this->assertSame([], WalletableFacade::transactionColumns());
        $this->assertFalse(WalletableFacade::allowsTransactionColumn('channel'));
    }

    public function testDeclaredColumnIsImmutableOnceWritten()
    {
        WalletableFacade::extendTransaction(['channel']);

        $wallet = $this->createWallet(0);
        $entry = (new Action($wallet, new TestColumnAction()))
            ->credit(1000, new ActionData($wallet, 'mobile'));

        $this->expectException(LedgerImmutableException::class);
        $entry->forceFill(['channel' => 'tampered'])->save();
    }
}
