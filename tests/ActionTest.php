<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\Event;
use Walletable\Events\ConfirmedTransaction;
use Walletable\Events\CreatedTransaction;
use Walletable\Internals\Actions\Action;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Internals\Argument;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Walletable;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\Transfer;
use Walletable\Transaction\TransferAction;

class ActionTest extends TestBench
{
    public function testCredit()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $this->setUpCurrencies();
        $wallet = $this->createWallet();

        $action = new Action($wallet, $actionObj = new CreditDebitAction());

        $action->credit(100000, new ActionData($wallet), 'Test Credit');

        $this->assertSame(100000, $wallet->refresh()->amount->integer());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(100000, $wallet->transactions->first()->amount->integer());
        $this->assertSame('Test Credit', $wallet->transactions->first()->remarks);
        $this->assertSame('Credit', $wallet->transactions->first()->title);
        $this->assertSame('credit', $wallet->transactions->first()->type);

        $this->assertTrue($actionObj->supportCredit());
        $this->assertTrue($actionObj->supportDebit());

        Event::assertDispatchedTimes(CreatedTransaction::class, 1);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 1);
    }

    public function testUnconfirmedCredit()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $this->setUpCurrencies();
        $wallet = $this->createWallet();

        $action = new Action($wallet, $actionObj = new CreditDebitAction());

        $action->unconfirmedCredit(100000, new ActionData($wallet), 'Test Unconfirmed Credit');
        $transaction = $wallet->transactions()->first();

        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(100000, $transaction->amount->integer());
        $this->assertSame(0, $transaction->balance->integer());
        $this->assertSame('Test Unconfirmed Credit', $transaction->remarks);
        $this->assertSame('Credit', $transaction->title);
        $this->assertSame('credit', $transaction->type);
        $this->assertNull($transaction->confirmed_at);
        $this->assertFalse($transaction->confirmed);

        $this->assertTrue($actionObj->supportCredit());
        $this->assertTrue($actionObj->supportDebit());

        Event::assertDispatchedTimes(CreatedTransaction::class, 1);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 0);

        $wallet->confirm($transaction);
        $this->assertSame(100000, $wallet->refresh()->amount->integer());
        $this->assertCount(1, $wallet->transactions()->get());
        $transaction = $wallet->transactions()->first();
        $this->assertSame(100000, $transaction->amount->integer());
        $this->assertSame(100000, $transaction->balance->integer());
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertTrue($transaction->confirmed);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 1);
    }

    public function testUnconfirmedDebit()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $this->setUpCurrencies();
        $wallet = $this->createWallet(1000000);

        $action = new Action($wallet, $actionObj = new CreditDebitAction());

        $action->unconfirmedDebit(500000, new ActionData($wallet), 'Test Unconfirmed Debit');
        $transaction = $wallet->transactions()->first();

        $this->assertSame(1000000, $wallet->refresh()->amount->integer());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(500000, $transaction->amount->integer());
        $this->assertSame(1000000, $transaction->balance->integer());
        $this->assertSame('Test Unconfirmed Debit', $transaction->remarks);
        $this->assertSame('Debit', $transaction->title);
        $this->assertSame('debit', $transaction->type);
        $this->assertFalse($transaction->confirmed);
        $this->assertNull($transaction->confirmed_at);

        $this->assertTrue($actionObj->supportCredit());
        $this->assertTrue($actionObj->supportDebit());

        Event::assertDispatchedTimes(CreatedTransaction::class, 1);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 0);

        $wallet->confirm($transaction);
        $this->assertSame(500000, $wallet->refresh()->amount->integer());
        $this->assertCount(1, $wallet->transactions()->get());
        $transaction = $wallet->transactions()->first();
        $this->assertSame(500000, $transaction->amount->integer());
        $this->assertSame(500000, $transaction->balance->integer());
        $this->assertNotNull($transaction->confirmed_at);
        $this->assertTrue($transaction->confirmed);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 1);
    }

    public function testDebit()
    {
        $this->setUpCurrencies();
        $wallet = $this->createWallet(100000);

        $action = new Action($wallet, new CreditDebitAction());

        $action->debit(100000, new ActionData($wallet), 'Test Debit');

        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(100000, $wallet->transactions->first()->amount->integer());
        $this->assertSame('Test Debit', $wallet->transactions->first()->remarks);
        $this->assertSame('Debit', $wallet->transactions->first()->title);
        $this->assertSame('debit', $wallet->transactions->first()->type);
    }

    public function testActionData()
    {
        $data = new ActionData(
            'Wellcome',
            200
        );

        $this->assertTrue($data->keyExists(0));
        $this->assertTrue($data->keyExists(1));
        $this->assertNotTrue($data->keyExists(2));

        $this->assertSame('Wellcome', $data->getKeyValue(0));
        $this->assertSame(200, $data->getKeyValue(1));
        $this->assertSame(null, $data->getKeyValue(2));

        $this->assertInstanceOf(Argument::class, $data->argument(0));
    }

    public function testActionManager()
    {

        $wallet = $this->createWallet(100000);

        ($transaction = new Transaction())->forceFill([
            'wallet_id' => $wallet->id,
            'session' => 'fhfherdfhfdhdfidhdfidfjfd',
            'type' => 'credit',
            'amount' => 100000,
            'balance' => 100000,
            'currency' => 'NGN',
            'action' => 'test',
            'remarks' => 'This is a test transaction',
            'meta' => '[]',
            'created_at' => now(),
        ])->save();

        $manager = new ActionManager(
            $transaction,
            new TestAction()
        );

        $this->assertSame('Test Transaction', $manager->title());
        $this->assertSame('/image/test/transaction.jpg', $manager->image());
    }

    public function testTransactionGetMethodResource()
    {

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $transfer = $wallet->transfer($wallet2, 50000, 'Test transfer');

        $transaction = $transfer->in();

        $this->assertSame($transaction->getMethodResource()->id, $wallet->walletable->id);
    }

    public function testTransfer()
    {
        TransferAction::methodResourceUsing(function ($action, $transaction) {
            return [
                'resource'
            ];
        });

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $transfer = $wallet->transfer($wallet2, 50000, 'Test transfer');

        $transaction = $transfer->in();

        $this->assertSame($transaction->getMethodResource(), [
            'resource'
        ]);
    }
}
