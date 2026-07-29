<?php

namespace Walletable\Tests;

use Walletable\Ledger\LedgerImmutableException;
use Walletable\Models\HouseAccount;
use Walletable\Tests\Models\Walletable;

/**
 * Asserts the structural invariants of the double-entry ledger and the
 * append-only behaviour of transactions and postings.
 */
class LedgerTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testCreditPostsBalancedTransaction()
    {
        $wallet = $this->createWallet(0);
        $tx = $wallet->credit(5000, 'Topup');

        $postings = $tx->postings()->get();
        $this->assertCount(2, $postings);

        $user = $postings->firstWhere('wallet_id', $wallet->id);
        $house = $postings->firstWhere('wallet_id', '!=', $wallet->id);

        $this->assertSame('C', $user->getRawOriginal('direction'));
        $this->assertSame('D', $house->getRawOriginal('direction'));
        $this->assertSame(5000, $user->amount->integer());
        $this->assertSame(5000, $house->amount->integer());

        // Per-currency invariant: sum across all wallets = 0.
        $this->assertSame(0, (int)\DB::table('wallets')->where('currency', 'NGN')->sum('amount'));
    }

    public function testTransferDoesNotTouchHouse()
    {
        $a = $this->createWallet(10000);
        $b = $this->createWallet(0, 'NGN', Walletable::create([
            'name' => 'Bisi',
            'email' => 'bisi@example.com',
        ]));

        // Seed house to balance the initial 10000.
        $house = HouseAccount::walletFor('NGN');
        $house->forceFill(['amount' => -10000])->save();

        $tx = $a->transfer($b, 4000);

        $this->assertCount(2, $tx->postings()->get());
        $this->assertSame(6000, $a->refresh()->amount->integer());
        $this->assertSame(4000, $b->refresh()->amount->integer());
        $this->assertSame(-10000, $house->refresh()->amount->integer());

        // Sum-to-zero invariant still holds.
        $this->assertSame(0, (int)\DB::table('wallets')->where('currency', 'NGN')->sum('amount'));
    }

    public function testPendingTransactionHasNoPostingsUntilConfirm()
    {
        $wallet = $this->createWallet(0);
        $pending = $wallet->unconfirmedCredit(2000);

        $this->assertSame('pending', $pending->status);
        $this->assertCount(0, $pending->postings()->get());
        $this->assertSame(0, $wallet->refresh()->amount->integer());

        $confirmed = $wallet->confirm($pending->refresh());

        $this->assertSame('posted', $confirmed->status);
        $this->assertCount(2, $confirmed->postings()->get());
        $this->assertSame(2000, $wallet->refresh()->amount->integer());
    }

    public function testPostingsAreAppendOnly()
    {
        $wallet = $this->createWallet(0);
        $tx = $wallet->credit(1000);
        $posting = $tx->postings()->first();

        $this->expectException(LedgerImmutableException::class);
        $posting->forceFill(['amount' => 1])->save();
    }

    public function testPostingsCannotBeDeleted()
    {
        $wallet = $this->createWallet(0);
        $tx = $wallet->credit(1000);
        $posting = $tx->postings()->first();

        $this->expectException(LedgerImmutableException::class);
        $posting->delete();
    }

    public function testTransactionFieldsAreImmutable()
    {
        $wallet = $this->createWallet(0);
        $tx = $wallet->credit(1000);

        $this->expectException(LedgerImmutableException::class);
        $tx->forceFill(['currency' => 'USD'])->save();
    }

    public function testTransactionStatusTransitionsAreRestricted()
    {
        $wallet = $this->createWallet(0);
        $tx = $wallet->credit(1000);

        // posted -> pending is illegal.
        $this->expectException(LedgerImmutableException::class);
        $tx->forceFill(['status' => 'pending'])->save();
    }

    public function testTransactionDeleteThrows()
    {
        $wallet = $this->createWallet(0);
        $tx = $wallet->credit(1000);

        $this->expectException(LedgerImmutableException::class);
        $tx->delete();
    }

    public function testHouseWalletCarriesNegativeBalance()
    {
        $wallet = $this->createWallet(0);
        $wallet->credit(7777);

        $house = HouseAccount::walletFor('NGN');
        $this->assertSame(-7777, $house->refresh()->amount->integer());
    }
}
