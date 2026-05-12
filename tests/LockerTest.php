<?php

namespace Walletable\Tests;

use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Internals\Lockers\OptimisticLocker;
use Walletable\Models\Posting;
use Walletable\Money\Money;

class LockerTest extends TestBench
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpCurrencies();
    }

    public function testApplyCredit()
    {
        $locker = new OptimisticLocker();
        $wallet = $this->createWallet();

        $this->assertSame(0, $wallet->refresh()->amount->integer());

        $balanceAfter = $locker->apply($wallet, Posting::DIRECTION_CREDIT, Money::NGN(100000));

        $this->assertSame(100000, $balanceAfter);
        $this->assertSame(100000, $wallet->refresh()->amount->integer());
    }

    public function testApplyDebit()
    {
        $locker = new OptimisticLocker();
        $wallet = $this->createWallet(100000);

        $balanceAfter = $locker->apply($wallet, Posting::DIRECTION_DEBIT, Money::NGN(100000));

        $this->assertSame(0, $balanceAfter);
        $this->assertSame(0, $wallet->refresh()->amount->integer());
    }

    public function testApplyDebitGuardsAgainstNegative()
    {
        $this->expectException(InsufficientBalanceException::class);

        $locker = new OptimisticLocker();
        $wallet = $this->createWallet(0);

        $locker->apply($wallet, Posting::DIRECTION_DEBIT, Money::NGN(1));
    }

    public function testApplyDebitAllowsNegativeForHouseWallets()
    {
        $locker = new OptimisticLocker();
        $wallet = $this->createWallet(0);

        $balanceAfter = $locker->apply($wallet, Posting::DIRECTION_DEBIT, Money::NGN(1000), true);

        $this->assertSame(-1000, $balanceAfter);
        $this->assertSame(-1000, $wallet->refresh()->amount->integer());
    }
}
