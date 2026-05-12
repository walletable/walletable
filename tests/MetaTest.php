<?php

namespace Walletable\Tests;

use Walletable\Tests\Models\Posting;
use Walletable\Tests\Models\Wallet;

class MetaTest extends TestBench
{
    public function testSetPostingMeta()
    {
        $posting = new Posting();
        $posting->forceFill(['meta' => []]);

        $posting->meta('test', 'This is a test arbitrary data');
        $posting->meta('extra.test', 'This is a test nested arbitrary data');
        $posting->meta('extra.sub.test', 'This is a test sub nested arbitrary data');

        $this->assertSame('This is a test arbitrary data', $posting->meta('test'));
        $this->assertSame('This is a test nested arbitrary data', $posting->meta('extra.test'));
        $this->assertSame('This is a test sub nested arbitrary data', $posting->meta('extra.sub.test'));
    }

    public function testSetWalletMeta()
    {
        $wallet = new Wallet();

        $wallet->meta('test', 'This is a test arbitrary data');
        $wallet->meta('extra.test', 'This is a test nested arbitrary data');
        $wallet->meta('extra.sub.test', 'This is a test sub nested arbitrary data');

        $this->assertSame('This is a test arbitrary data', $wallet->meta('test'));
        $this->assertSame('This is a test nested arbitrary data', $wallet->meta('extra.test'));
        $this->assertSame('This is a test sub nested arbitrary data', $wallet->meta('extra.sub.test'));
    }
}
