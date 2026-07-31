<?php

namespace Walletable\Tests;

use Illuminate\Support\Facades\Event;
use Walletable\Events\TransactionConfirmed;
use Walletable\Events\TransactionCreating;
use Walletable\Events\TransactionPosted;
use Walletable\Exceptions\IncompatibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Mutator;
use Walletable\Internals\Actions\Action;
use Walletable\Models\HouseAccount;
use Walletable\Money\Money;
use Walletable\Tests\Models\Posting;
use Walletable\Tests\Models\Transaction;
use Walletable\Tests\Models\Wallet;
use Walletable\Tests\Models\Walletable;
use Walletable\Transaction\CreditDebitAction;
use Walletable\Transaction\TransferAction;

class WalletTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testCompatible()
    {
        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(100000);
        $wallet3 = $this->createWallet(100000, 'USD');

        $this->assertTrue($wallet->compatible($wallet2));
        $this->assertFalse($wallet->compatible($wallet3));
        $this->assertFalse($wallet2->compatible($wallet3));
    }

    public function testCompactibleStillWorksButIsDeprecated()
    {
        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(100000);

        $deprecations = [];
        set_error_handler(function ($severity, $message) use (&$deprecations) {
            $deprecations[] = $message;
            return true;
        }, E_USER_DEPRECATED);

        try {
            $this->assertTrue($wallet->compactible($wallet2));
        } finally {
            restore_error_handler();
        }

        $this->assertCount(1, $deprecations);
        $this->assertStringContainsString('use Wallet::compatible()', $deprecations[0]);
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
        Event::fake([TransactionPosted::class]);

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Abisade Ilesanmi',
            'email' => 'bisade@bisade.com',
        ]));

        $entry = $wallet->transfer($wallet2, 50000, 'Test transfer');

        $this->assertSame(50000, $wallet->refresh()->amount->integer());
        $this->assertSame(50000, $wallet2->refresh()->amount->integer());
        $this->assertCount(2, $entry->postings()->get());

        $debit = $entry->postings()->where('direction', 'D')->first();
        $credit = $entry->postings()->where('direction', 'C')->first();

        $this->assertSame($wallet->id, $debit->wallet_id);
        $this->assertSame($wallet2->id, $credit->wallet_id);
        $this->assertSame('Abisade Ilesanmi', $debit->title);
        $this->assertSame('Olawale Ilesanmi', $credit->title);
        $this->assertSame('Test transfer', $entry->narration);

        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testTransferInsuficientFund()
    {
        Event::fake([TransactionPosted::class]);
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage(
            "Insufficient wallet balance, The wallet balance is less than ₦5,000"
        );

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet();

        $wallet->transfer($wallet2, 500000, 'Test transfer');
    }

    public function testTransferIncompatible()
    {
        Event::fake([TransactionPosted::class]);
        $this->expectException(IncompatibleWalletsException::class);
        $this->expectExceptionMessage(
            'Can`t perform any operations between two incompatible wallets'
        );

        $wallet = $this->createWallet(100000);
        $wallet2 = $this->createWallet(0, 'USD');

        $wallet->transfer($wallet2, 50000, 'Test transfer');
    }

    public function testCredit()
    {
        Event::fake([TransactionPosted::class]);
        $wallet = $this->createWallet(0);

        $entry = $wallet->credit(50000, 'Test Credit', 'Crediting in test runtime');

        $this->assertSame(50000, $wallet->refresh()->amount->integer());
        $this->assertCount(2, $entry->postings()->get());

        $userPosting = $entry->postings()->where('wallet_id', $wallet->id)->first();
        $this->assertSame('Test Credit', $userPosting->title);
        $this->assertSame('credit', $userPosting->type);

        $this->assertSame('Crediting in test runtime', $entry->narration);

        // House wallet is the mirror: negative balance equal to the credit.
        $house = HouseAccount::walletFor('NGN');
        $this->assertSame(-50000, $house->refresh()->amount->integer());

        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testDebit()
    {
        Event::fake([TransactionPosted::class]);
        $wallet = $this->createWallet(50000);
        $house = HouseAccount::walletFor('NGN');

        // Seed house balance to mirror the user wallet's initial 50000:
        // sum-to-zero invariant must hold from the start.
        $house->forceFill(['amount' => -50000])->save();

        $entry = $wallet->debit(50000, 'Test Debit', 'Debiting in test runtime');

        $this->assertSame(0, $wallet->refresh()->amount->integer());
        $this->assertCount(2, $entry->postings()->get());

        $userPosting = $entry->postings()->where('wallet_id', $wallet->id)->first();
        $this->assertSame('Test Debit', $userPosting->title);
        $this->assertSame('debit', $userPosting->type);

        // House absorbed the debit: now back to 0.
        $this->assertSame(0, $house->refresh()->amount->integer());

        Event::assertDispatchedTimes(TransactionPosted::class, 1);
    }

    public function testDebitInsuficientBalance()
    {
        $this->expectException(InsufficientBalanceException::class);
        $this->expectExceptionMessage(
            "Insufficient wallet balance, The wallet balance is less than ₦500"
        );

        $wallet = $this->createWallet(0);

        $wallet->debit(50000, 'Test Debit', 'Debiting in test runtime');
    }

    /**
     * An overdraft is refused before anything observable happens: no listener
     * is told about a draft that is going to be rejected, and no row survives.
     */
    public function testOverdraftIsRefusedBeforeAnythingIsWritten()
    {
        Event::fake([TransactionCreating::class, TransactionPosted::class]);

        $wallet = $this->createWallet(0);

        try {
            $wallet->debit(50000, 'Test Debit');
            $this->fail('Expected an ' . InsufficientBalanceException::class);
        } catch (InsufficientBalanceException $exception) {
            //
        }

        Event::assertNotDispatched(TransactionCreating::class);
        Event::assertNotDispatched(TransactionPosted::class);
        $this->assertSame(0, Transaction::query()->count());
        $this->assertSame(0, Posting::query()->count());
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
            /** @var Wallet $this */
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

    public function testTransactionConfirmedEventOnConfirm()
    {
        Event::fake([TransactionConfirmed::class]);
        $wallet = $this->createWallet(0);

        $pending = $wallet->unconfirmedCredit(1000, 'Pending');
        $this->assertSame('pending', $pending->status);

        $wallet->confirm($pending->refresh());

        Event::assertDispatchedTimes(TransactionConfirmed::class, 1);
    }
}
