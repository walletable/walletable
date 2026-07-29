<?php

namespace Walletable\Tests;

use Walletable\Exceptions\ConcurrencyException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Internals\Lockers\OptimisticLocker;
use Walletable\Models\Posting;
use Walletable\Models\Wallet;
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

    public function testApplyRetriesWhenTheCompareAndSwapLoses()
    {
        $locker = new LosingLocker(2);
        $wallet = $this->createWallet(0);

        $balanceAfter = $locker->apply($wallet, Posting::DIRECTION_CREDIT, Money::NGN(500));

        $this->assertSame(500, $balanceAfter);
        $this->assertSame(3, $locker->attempts);
    }

    public function testApplyGivesUpOnAPermanentlyContendedWallet()
    {
        $locker = new LosingLocker(PHP_INT_MAX, 3);
        $wallet = $this->createWallet(0);

        try {
            $locker->apply($wallet, Posting::DIRECTION_CREDIT, Money::NGN(500));
            $this->fail('Expected a ConcurrencyException.');
        } catch (ConcurrencyException $exception) {
            $this->assertSame(3, $exception->getAttempts());
            $this->assertSame($wallet->getKey(), $exception->getWallet()->getKey());
        }

        $this->assertSame(3, $locker->attempts);
        $this->assertSame(0, $wallet->refresh()->amount->integer());
    }
}

/**
 * Loses the CAS race a fixed number of times before behaving normally, so the
 * retry and starvation paths can be exercised without real concurrency.
 */
class LosingLocker extends OptimisticLocker
{
    public int $attempts = 0;

    protected int $losses;

    public function __construct(int $losses, int $maxAttempts = 8)
    {
        parent::__construct($maxAttempts, 0, 0);

        $this->losses = $losses;
    }

    protected function compareAndSwap(Wallet $wallet, $expected, int $new): bool
    {
        $this->attempts++;

        if ($this->losses-- > 0) {
            return false;
        }

        return parent::compareAndSwap($wallet, $expected, $new);
    }
}
