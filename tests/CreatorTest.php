<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Walletable\Events\CreatedWallet;
use Walletable\Events\CreatingWallet;
use Walletable\Internals\Creator;
use Walletable\Models\Wallet;
use Walletable\Tests\Models\Wallet as ModelsWallet;
use Walletable\Tests\Models\Walletable;

class CreatorTest extends TestBench
{
    public function testSetValue()
    {
        $creator = new Creator(Walletable::create([
            'name' => 'Olawale Ilesanmi',
            'email' => 'olawale@olawale.com',
        ]));

        $this->assertFalse($creator->filled());

        $creator->label('Main Wallet')
            ->currency('NGN')
            ->email('olawale@olawale.com')
            ->tag('main');

        $this->assertTrue($creator->filled());
    }

    public function testFilledException()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Missing value(s): email,label,tag,currency'
        );

        $creator = new Creator(Walletable::create([
            'name' => 'Olawale Ilesanmi',
            'email' => 'olawale@olawale.com',
        ]));

        $creator->filled(true);
    }

    public function testNewWalletModel()
    {
        $creator = new Creator(Walletable::create([
            'name' => 'Olawale Ilesanmi',
            'email' => 'olawale@olawale.com',
        ]));

        $this->assertInstanceOf(Wallet::class, $creator->newWalletModel());
        $this->assertInstanceOf(ModelsWallet::class, $creator->newWalletModel());
    }

    public function testCreateMethod()
    {
        Event::fake([
            CreatingWallet::class,
            CreatedWallet::class
        ]);

        $creator = new Creator(Walletable::create([
            'name' => 'Olawale Ilesanmi',
            'email' => 'olawale@olawale.com',
        ]));

        $creator->label('Main Wallet')
            ->currency('NGN')
            ->email('olawale@olawale.com')
            ->tag('main');

        $this->assertInstanceOf(Wallet::class, $wallet = $creator->create());
        $this->assertInstanceOf(ModelsWallet::class, $wallet);
        $this->assertSame(0, $wallet->refresh()->amount->getInt());

        Event::assertDispatched(CreatingWallet::class);
        Event::assertDispatched(CreatedWallet::class);
    }
}
