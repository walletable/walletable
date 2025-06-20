<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\Event;
use Walletable\Events\ConfirmedTransaction;
use Walletable\Events\CreatedTransaction;
use Walletable\Exceptions\IncompactibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Mutator;
use Walletable\Internals\Actions\Action;
use Walletable\Money\Money;
use Walletable\Tests\Models\Wallet;
use Walletable\Tests\Models\Walletable;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\TransferAction;

class WalletTest extends TestBench
{
    public function testCompactable()
    {

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(100000);
        $wallet3 = $this->createWallet(100000, 'USD');

        $this->assertTrue($wallet->compactible($wallet2));
        $this->assertFalse($wallet->compactible($wallet3));
        $this->assertFalse($wallet2->compactible($wallet3));
    }

    public function testMoney()
    {

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(100000, 'USD');

        $this->assertSame('NGN', $wallet->money(100000)->getCurrency()->getCode());
        $this->assertSame('USD', $wallet2->money(100000)->getCurrency()->getCode());
    }

    public function testTransfer()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $transfer = $wallet->transfer($wallet2, 50000, 'Test transfer');

        $this->assertSame(2, $transfer->getTransactions()->count());
        $this->assertSame(50000, $transfer->getAmount()->integer());
        $this->assertTrue($transfer->successful());

        $this->assertSame(50000, $wallet->refresh()->amount->integer());
        $this->assertSame(50000, $wallet2->refresh()->amount->integer());

        $this->assertSame(1, $wallet->transactions()->count());
        $this->assertSame(1, $wallet2->transactions()->count());

        $trx1 = $wallet->transactions->first();
        $trx2 = $wallet2->transactions->first();

        $this->assertSame('Abisade Ilesanmi', $trx1->title);
        $this->assertSame('Olawale Ilesanmi', $trx2->title);

        $this->assertSame('Test transfer', $trx1->remarks);
        $this->assertSame('Test transfer', $trx2->remarks);

        $this->assertSame('debit', $trx1->type);
        $this->assertSame('credit', $trx2->type);

        $trx1 = $transfer->out();
        $trx2 = $transfer->in();

        $this->assertSame('Abisade Ilesanmi', $trx1->title);
        $this->assertSame('Olawale Ilesanmi', $trx2->title);

        $this->assertSame('Test transfer', $trx1->remarks);
        $this->assertSame('Test transfer', $trx2->remarks);

        $this->assertSame('debit', $trx1->type);
        $this->assertSame('credit', $trx2->type);

        $this->assertSame($wallet->walletable->id, $trx2->method->id);
        $this->assertSame($wallet2->walletable->id, $trx1->method->id);

        Event::assertDispatchedTimes(CreatedTransaction::class, 2);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 2);
    }

    public function testTransferInsuficientFund()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage(
            "Insufficient wallet balance, The wallet ballance is less than ₦5,000"
        );

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet();

        $wallet->transfer($wallet2, 500000, 'Test transfer');

        Event::assertDispatchedTimes(CreatedTransaction::class, 0);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 0);
    }

    public function testTransferIncompactable()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $this->expectException(IncompactibleWalletsException::class);
        $this->expectExceptionMessage(
            'Can`t perform any operations between two incompactible wallets'
        );

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'USD');

        $wallet->transfer($wallet2, 50000, 'Test transfer');

        Event::assertDispatchedTimes(CreatedTransaction::class, 0);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 0);
    }

    public function testCredit()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $wallet = $this->createWallet(0);

        $credit = $wallet->credit(50000, 'Test Credit', 'Crediting in test runtime');

        $this->assertSame(1, $credit->getTransactions()->count());
        $this->assertSame(50000, $credit->getAmount()->integer());

        $this->assertSame(50000, $wallet->refresh()->amount->integer());

        $this->assertSame(1, $wallet->transactions()->count());

        $trx = $wallet->transactions->first();

        $this->assertSame('Test Credit', $trx->title);
        $this->assertSame('Crediting in test runtime', $trx->remarks);

        $this->assertSame('credit', $trx->type);

        Event::assertDispatchedTimes(CreatedTransaction::class, 1);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 1);
    }

    public function testDebit()
    {
        Event::fake([
            CreatedTransaction::class,
            ConfirmedTransaction::class,
        ]);
        $wallet = $this->createWallet(50000);

        $debit = $wallet->debit(50000, 'Test Debit', 'Debiting in test runtime');

        $this->assertSame(1, $debit->getTransactions()->count());
        $this->assertSame(50000, $debit->getAmount()->integer());

        $this->assertSame(0, $wallet->refresh()->amount->integer());

        $this->assertSame(1, $wallet->transactions()->count());

        $trx = $wallet->transactions->first();

        $this->assertSame('Test Debit', $trx->title);
        $this->assertSame('Debiting in test runtime', $trx->remarks);

        $this->assertSame('debit', $trx->type);

        Event::assertDispatchedTimes(CreatedTransaction::class, 1);
        Event::assertDispatchedTimes(ConfirmedTransaction::class, 1);
    }

    public function testDebitInsuficientBalance()
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage(
            "Insufficient wallet balance, The wallet ballance is less than ₦500"
        );

        $wallet = $this->createWallet(0);

        $wallet->debit(50000, 'Test Debit', 'Debiting in test runtime');
    }

    public function testActionObjects()
    {
        $wallet = $this->createWallet(0);

        $this->assertInstanceOf(Action::class, $wallet->action('transfer'));
        $this->assertInstanceOf(TransferAction::class, $wallet->action('transfer')->getAction());
        $this->assertInstanceOf(CreditDebitAction::class, $wallet->action('credit_debit')->getAction());
    }

    public function testMacroable()
    {
        Wallet::macro('testMacro', function () {
            /**
             * @var Wallet $this
             */
            return $this->amount->value();
        });

        Wallet::macro('testStaticMacro', function () {
            return 'Wallet Macro';
        });

        $wallet = $this->createWallet(100000);

        $this->assertSame('100000', $wallet->testMacro());
        $this->assertSame('Wallet Macro', Wallet::testStaticMacro());
    }

    public function testWalletBalanceMutation()
    {
        $wallet = $this->createWallet(100000);

        Mutator::mutator('wallet.balance', function ($mutation) {
            $mutation->setValue(
                $mutation->value()->add(
                    Money::NGN(100000)
                )
            );
        });

        $this->assertSame(200000, $wallet->balance->integer());
        $this->assertSame(100000, $wallet->amount->integer());
    }

    public function testWalletBalanceMutationHooks()
    {
        $wallet = $this->createWallet(100000);

        Mutator::mutator('wallet.balance', function ($mutation) {
            $amount = $mutation->value()->add(
                Money::NGN(100000)
            );

            $this->assertSame($amount, $mutation->value());
        });

        $amount = $wallet->balance->add(
            Money::NGN(100000)
        );

        $this->assertNotSame($wallet->balance, $amount);
    }
}
