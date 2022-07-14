<?php

namespace Walletable\Tests;

use Walletable\Events\CreatedWallet;
use Walletable\Events\CreatingWallet;
use Walletable\Tests\Models\Wallet;
use Walletable\Tests\Models\Walletable;

class EventTest extends TestBench
{
    public function testCreatedEvent()
    {
        $event = new CreatedWallet(
            $wallet = new Wallet(),
            $walletable = new Walletable()
        );

        $this->assertSame($event->wallet, $wallet);
        $this->assertSame($event->walletable, $walletable);
    }

    public function testCreatingEvent()
    {
        $event = new CreatingWallet(
            $wallet = new Wallet(),
            $walletable = new Walletable()
        );

        $this->assertSame($event->wallet, $wallet);
        $this->assertSame($event->walletable, $walletable);
    }
}
