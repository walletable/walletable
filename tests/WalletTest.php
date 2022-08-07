<?php

namespace Walletable\Tests;

use Walletable\Exceptions\IncompactibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Internals\Actions\Action;
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

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $transfer = $wallet->transfer($wallet2, 50000, 'Test transfer');

        $this->assertSame(2, $transfer->getTransactions()->count());
        $this->assertSame(50000, $transfer->getAmount()->getInt());
        $this->assertTrue($transfer->successful());

        $this->assertSame(50000, $wallet->refresh()->amount->getInt());
        $this->assertSame(50000, $wallet2->refresh()->amount->getInt());

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
    }

    public function testTransferInsuficientFund()
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage(
            "Insufficient wallet balance, The wallet ballance is less than ₦\u{00a0}5,000.00"
        );

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet();

        $wallet->transfer($wallet2, 500000, 'Test transfer');
    }

    public function testTransferIncompactable()
    {
        $this->expectException(IncompactibleWalletsException::class);
        $this->expectExceptionMessage(
            'Can`t perform any operations between two incompactible wallets'
        );

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'USD');

        $wallet->transfer($wallet2, 50000, 'Test transfer');
    }

    public function testCredit()
    {
        $wallet = $this->createWallet(0);

        $credit = $wallet->credit(50000, 'Test Credit', 'Crediting in test runtime');

        $this->assertSame(1, $credit->getTransactions()->count());
        $this->assertSame(50000, $credit->getAmount()->getInt());

        $this->assertSame(50000, $wallet->refresh()->amount->getInt());

        $this->assertSame(1, $wallet->transactions()->count());

        $trx = $wallet->transactions->first();

        $this->assertSame('Test Credit', $trx->title);
        $this->assertSame('Crediting in test runtime', $trx->remarks);

        $this->assertSame('credit', $trx->type);
    }

    public function testDebit()
    {
        $wallet = $this->createWallet(50000);

        $debit = $wallet->debit(50000, 'Test Debit', 'Debiting in test runtime');

        $this->assertSame(1, $debit->getTransactions()->count());
        $this->assertSame(50000, $debit->getAmount()->getInt());

        $this->assertSame(0, $wallet->refresh()->amount->getInt());

        $this->assertSame(1, $wallet->transactions()->count());

        $trx = $wallet->transactions->first();

        $this->assertSame('Test Debit', $trx->title);
        $this->assertSame('Debiting in test runtime', $trx->remarks);

        $this->assertSame('debit', $trx->type);
    }

    public function testDebitInsuficientBalance()
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage(
            "Insufficient wallet balance, The wallet ballance is less than ₦\u{00a0}500.00"
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
            return $this->amount->getAmount();
        });

        Wallet::macro('testStaticMacro', function () {
            return 'Wallet Macro';
        });

        $wallet = $this->createWallet(100000);

        $this->assertSame('100000', $wallet->testMacro());
        $this->assertSame('Wallet Macro', Wallet::testStaticMacro());
    }
}
