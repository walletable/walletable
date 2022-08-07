<?php

namespace Walletable\Tests;

use Walletable\Internals\Actions\Action;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Internals\Argument;
use Walletable\Tests\Models\Transaction;
use Walletable\Transaction\CreditDebitAction;

class ActionTest extends TestBench
{
    public function testCredit()
    {
        $this->setUpCurrencies();
        $wallet = $this->createWallet();

        $action = new Action($wallet, $actionObj = new CreditDebitAction());

        $action->credit(100000, new ActionData($wallet), 'Test Credit');

        $this->assertSame(100000, $wallet->refresh()->amount->getInt());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(100000, $wallet->transactions->first()->amount->getInt());
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

        $this->assertSame(0, $wallet->refresh()->amount->getInt());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(100000, $wallet->transactions->first()->amount->getInt());
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
}
