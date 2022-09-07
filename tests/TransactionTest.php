<?php

namespace Walletable\Tests;

use Walletable\Internals\Actions\ActionManager;
use Walletable\Money\Currency;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Wallet;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\TransferAction;

class TransactionTest extends TestBench
{
    public function testTransaction()
    {

        $wallet = $this->createWallet(100000);

        ($transaction = new Transaction())->forceFill([
            'wallet_id' => $wallet->id,
            'session' => 'fhfherdfhfdhdfidhdfidfjfd',
            'type' => 'credit',
            'amount' => 100000,
            'balance' => 100000,
            'currency' => 'NGN',
            'action' => 'credit_debit',
            'remarks' => 'This is a test transaction',
            'meta' => ['title' => 'Test Credit'],
            'created_at' => now(),
        ])->save();

        $this->assertSame(100000, $transaction->amount->integer());
        $this->assertSame(100000, $transaction->balance->integer());
        $this->assertInstanceOf(ActionManager::class, $transaction->action);
        $this->assertInstanceOf(Currency::class, $transaction->currency);
        $this->assertSame('Test Credit', $transaction->title);
        $this->assertSame(null, $transaction->image);
    }

    public function testTransactionActionCreditDebit()
    {

        $wallet = $this->createWallet(100000);

        ($transaction = new Transaction())->forceFill([
            'wallet_id' => $wallet->id,
            'session' => 'fhfherdfhfdhdfidhdfidfjfd',
            'type' => 'credit',
            'amount' => 100000,
            'balance' => 100000,
            'currency' => 'NGN',
            'action' => 'credit_debit',
            'remarks' => 'This is a test transaction',
            'meta' => ['title' => 'Test Credit'],
            'created_at' => now(),
        ])->save();

        $this->assertInstanceOf(CreditDebitAction::class, $transaction->action->getAction());
    }

    public function testTransactionActionTransfer()
    {

        $wallet = $this->createWallet(100000);

        ($transaction = new Transaction())->forceFill([
            'wallet_id' => $wallet->id,
            'session' => 'fhfherdfhfdhdfidhdfidfjfd',
            'type' => 'credit',
            'amount' => 100000,
            'balance' => 100000,
            'currency' => 'NGN',
            'action' => 'transfer',
            'remarks' => 'This is a test transaction',
            'meta' => ['title' => 'Test Credit'],
            'created_at' => now(),
        ])->save();

        $this->assertInstanceOf(TransferAction::class, $transaction->action->getAction());
    }

    public function testMacroable()
    {
        Transaction::macro('testMacro', function () {
            /**
             * @var Transaction $this
             */
            return $this->amount->value();
        });

        Transaction::macro('testStaticMacro', function () {
            return 'Transaction Macro';
        });

        $wallet = $this->createWallet(100000);

        ($transaction = new Transaction())->forceFill([
            'wallet_id' => $wallet->id,
            'session' => 'fhfherdfhfdhdfidhdfidfjfd',
            'type' => 'credit',
            'amount' => 100000,
            'balance' => 100000,
            'currency' => 'NGN',
            'action' => 'transfer',
            'remarks' => 'This is a test transaction',
            'meta' => ['title' => 'Test Credit'],
            'created_at' => now(),
        ])->save();

        $this->assertSame('100000', $transaction->testMacro());
        $this->assertSame('Transaction Macro', Transaction::testStaticMacro());
    }
}
