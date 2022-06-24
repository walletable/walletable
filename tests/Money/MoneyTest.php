<?php

namespace Walletable\Tests\Money;

use InvalidArgumentException;
use Walletable\Money\Money;
use Walletable\Tests\TestBench as TestCase;

class MoneyTest extends TestCase
{
    public function testCreateMoney()
    {
        $this->setUpCurrencies();
        $naira = Money::NGN(100000);
        $naira2 = new Money(100000, Money::currency('NGN'));

        $this->assertSame($naira->getCurrency(), $naira2->getCurrency());
        $this->assertSame('NGN', $naira->getCurrency()->getCode());
    }

    public function testUsingNotSupported()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('[LRD] currency not supported.');
        Money::LRD(100000);
    }

    public function testAdditionOfMoney()
    {
        $naira = Money::NGN(100000);
        $naira2 = Money::NGN(100000);

        $this->assertSame(200000, $naira->add($naira2)->getInt());
    }

    public function testAdditionOfMultipleMoney()
    {
        $naira = Money::NGN(100000);
        $naira2 = Money::NGN(100000);
        $naira3 = Money::NGN(100000);

        $this->assertSame(300000, $naira->add($naira2, $naira3)->getInt());
    }

    public function testSumOfMoney()
    {
        $naira = Money::NGN(200000);
        $naira2 = Money::NGN(100000);
        $naira3 = Money::NGN(100000);

        $this->assertSame(400000, Money::sum($naira, $naira2, $naira3)->getInt());
    }

    public function testSubtractionOfMoney()
    {
        $naira = Money::NGN(200000);
        $naira2 = Money::NGN(100000);

        $this->assertSame(100000, $naira->subtract($naira2)->getInt());
    }

    public function testSubtractionOfMultipleMoney()
    {
        $naira = Money::NGN(200000);
        $naira2 = Money::NGN(100000);
        $naira3 = Money::NGN(50000);

        $this->assertSame(50000, $naira->subtract($naira2, $naira3)->getInt());
    }

    public function testDivisionOfMoney()
    {
        $naira = Money::NGN(200000);

        $this->assertSame(100000, $naira->divide(2)->getInt());
    }

    public function testDivisionOfMoneyWithDecimal()
    {
        $naira = Money::NGN(3234323);

        $this->assertSame(5390538, $naira->divide(0.6)->getInt());
    }

    public function testDivisionOfMoneyWithRoundMode()
    {
        $naira = Money::NGN(3234323);

        $this->assertSame(4042903, $naira->divide(0.8, Money::ROUND_DOWN)->getInt());
    }

    public function testMultiplicationOfMoney()
    {
        $naira = Money::NGN(100000);

        $this->assertSame(200000, $naira->multiply(2)->getInt());
    }

    public function testMultiplicationOfMoneyWithDecimal()
    {
        $naira = Money::NGN(3234323);

        $this->assertSame(1940594, $naira->multiply(0.6)->getInt());
    }

    public function testMultiplicationOfMoneyWithRoundMode()
    {
        $naira = Money::NGN(3234323);

        $this->assertSame(2910890, $naira->multiply(0.9, Money::ROUND_DOWN)->getInt());
    }

    public function testAllocationOfMoney()
    {
        $nairas = Money::NGN(200000)->allocateTo(4);

        foreach ($nairas as $money) {
            $this->assertSame(50000, $money->getInt());
        }
    }

    public function testAllocationOfMoneyWithExtra()
    {
        $nairas = Money::NGN(200001)->allocateTo(4);

        foreach ($nairas as $key => $money) {
            if ($key === 0) {
                $this->assertSame(50001, $money->getInt());
                continue;
            }

            $this->assertSame(50000, $money->getInt());
        }
    }
}
