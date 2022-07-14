<?php

namespace Walletable\Tests;

use Walletable\Internals\Actions\Action;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Internals\Argument;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Walletable;
use Walletable\Transaction\CreditDebitAction;

class ActionTest extends TestBench
{
    public function testCredit()
    {
        $this->setUpCurrencies();
        $wallet = $this->createWallet();

        $action = new Action($wallet, new CreditDebitAction());

        $action->credit(100000, new ActionData($wallet), 'Test Credit');

        $this->assertSame(100000, $wallet->refresh()->amount->getInt());
        $this->assertCount(1, $wallet->transactions);
        $this->assertSame(100000, $wallet->transactions->first()->amount->getInt());
        $this->assertSame('Test Credit', $wallet->transactions->first()->remarks);
        $this->assertSame('Credit', $wallet->transactions->first()->title);
        $this->assertSame('credit', $wallet->transactions->first()->type);
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

    public function testArgument()
    {
        $data = new ActionData(
            'Wellcome',
            200,
            true,
            '234',
            4.5,
            [4, 5],
            function () {
                # code...
            },
            '89099876',
            Walletable::create([
                'name' => 'Olawale Ilesanmi',
                'email' => 'olawale@olawale.com',
            ])
        );



        $this->assertSame('Wellcome', (new Argument($data, 0))->value());

        $this->assertInstanceOf(Argument::class, $data->argument(0)->required());
        $this->assertInstanceOf(Argument::class, $data->argument(0)->notEmpty());
        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('str'));
        $this->assertInstanceOf(Argument::class, $data->argument(0)->type('string'));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->type('int'));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->type('integer'));
        $this->assertInstanceOf(Argument::class, $data->argument(2)->type('bool'));
        $this->assertInstanceOf(Argument::class, $data->argument(2)->type('boolean'));
        $this->assertInstanceOf(Argument::class, $data->argument(3)->type('num'));
        $this->assertInstanceOf(Argument::class, $data->argument(3)->type('numeric'));
        $this->assertInstanceOf(Argument::class, $data->argument(4)->type('num'));
        $this->assertInstanceOf(Argument::class, $data->argument(4)->type('numeric'));
        $this->assertInstanceOf(Argument::class, $data->argument(5)->type('array'));
        $this->assertInstanceOf(Argument::class, $data->argument(6)->type('closure'));
        $this->assertInstanceOf(Argument::class, $data->argument(7)->type('digit'));

        $this->assertInstanceOf(Argument::class, $data->argument(8)->isA(Walletable::class));

        $this->assertInstanceOf(Argument::class, $data->argument(1)->between(191, 201));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->min(191));
        $this->assertInstanceOf(Argument::class, $data->argument(1)->max(201));
    }
}
