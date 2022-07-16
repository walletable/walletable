<?php

namespace Walletable\Tests;

use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Wallet;

class MetaTest extends TestBench
{
    public function testSetTransactionMeta()
    {
        $wallet = $this->createWallet(100000);

        ($transaction = new Transaction())->forceFill([
            'meta' => '[]'
        ]);

        $transaction->meta('test', 'This is a test arbitrary data');
        $transaction->meta('extra.test', 'This is a test nested arbitrary data');
        $transaction->meta('extra.sub.test', 'This is a test sub nested arbitrary data');

        $this->assertSame($transaction->meta('test'), 'This is a test arbitrary data');
        $this->assertSame($transaction->meta('extra.test'), 'This is a test nested arbitrary data');
        $this->assertSame($transaction->meta('extra.sub.test'), 'This is a test sub nested arbitrary data');
    }

    public function testSetWalletMeta()
    {
        $wallet = new Wallet();

        $wallet->meta('test', 'This is a test arbitrary data');
        $wallet->meta('extra.test', 'This is a test nested arbitrary data');
        $wallet->meta('extra.sub.test', 'This is a test sub nested arbitrary data');

        $this->assertSame($wallet->meta('test'), 'This is a test arbitrary data');
        $this->assertSame($wallet->meta('extra.test'), 'This is a test nested arbitrary data');
        $this->assertSame($wallet->meta('extra.sub.test'), 'This is a test sub nested arbitrary data');
    }
}
