<?php

namespace Walletable\Tests;

use Walletable\Tests\Models\Transaction;
use Walletable\Transaction\TransactionBag;

class TransactionBagTest extends TestBench
{
    public function testNew()
    {
        $wallet = $this->createWallet(100000);
        $bag = new TransactionBag();

        $this->assertInstanceOf(Transaction::class, $trx = $bag->new($wallet, [
            'remarks' => 'Testing new transaction from transaction bag.'
        ]));

        $trx->syncOriginal();

        $this->assertSame(1, $bag->count());
        $this->assertSame('Testing new transaction from transaction bag.', $trx->remarks);
    }

    public function testAdd()
    {
        $wallet = $this->createWallet(100000);
        $bag = new TransactionBag();

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

        $bag->new($wallet, [
            'remarks' => 'Testing new transaction from transaction bag.'
        ]);

        $this->assertInstanceOf(TransactionBag::class, $bag->add($transaction));

        $this->assertSame(2, $bag->count());
    }

    public function testSave()
    {
        $wallet = $this->createWallet(100000);
        $bag = new TransactionBag();

        $bag->new($wallet, [
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
        ]);

        $bag->new($wallet, [
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
        ]);

        $bag->new($wallet, [
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
        ]);

        $bag->save();

        $this->assertCount(3, Transaction::all());
    }
}
