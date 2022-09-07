<?php

namespace Walletable\Tests;

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
