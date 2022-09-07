<?php

namespace Walletable\Tests\Money;

use InvalidArgumentException;
use Walletable\Money\Currency;
use Walletable\Money\Formatter\IntlMoneyFormatter;
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
        $this->assertSame('100000', $naira->getAmount());
        $this->assertSame(100000, $naira->getInt());
        $this->assertInstanceOf(Currency::class, $naira->getCurrency());
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

    public function testGreaterThan()
    {
        $money = Money::NGN(200000);
        $this->assertTrue($money->greaterThan(Money::NGN(100000)));
        $this->assertNotTrue($money->greaterThan(Money::NGN(300000)));
    }

    public function testGreaterThanOrEqual()
    {
        $money = Money::NGN(200000);
        $this->assertTrue($money->greaterThanOrEqual(Money::NGN(100000)));
        $this->assertTrue($money->greaterThanOrEqual(Money::NGN(200000)));
        $this->assertNotTrue($money->greaterThanOrEqual(Money::NGN(300000)));
    }

    public function testLessThan()
    {
        $money = Money::NGN(200000);
        $this->assertNotTrue($money->lessThan(Money::NGN(100000)));
        $this->assertTrue($money->lessThan(Money::NGN(300000)));
    }

    public function testLessThanOrEqual()
    {
        $money = Money::NGN(200000);
        $this->assertNotTrue($money->lessThanOrEqual(Money::NGN(100000)));
        $this->assertTrue($money->lessThanOrEqual(Money::NGN(300000)));
        $this->assertTrue($money->lessThanOrEqual(Money::NGN(200000)));
    }

    public function testRatioOf()
    {
        $money = Money::NGN(100000);

        $this->assertSame('0.5', $money->ratioOf(Money::NGN(200000)));
    }

    public function testAbsolute()
    {
        $money = Money::NGN(-100000);
        $money2 = Money::NGN(100000);

        $this->assertSame(100000, $money->absolute()->getInt());
        $this->assertSame(100000, $money2->absolute()->getInt());
    }

    public function testNegative()
    {
        $money = Money::NGN(100000);

        $this->assertTrue($money->negative()->isNegative());
        $this->assertSame('-100000', $money->negative()->getAmount());
        $this->assertSame(-100000, $money->negative()->getInt());
    }

    public function testIsPositive()
    {
        $money = Money::NGN(100000);

        $this->assertTrue($money->isPositive());
    }

    public function testIsZero()
    {
        $money = Money::NGN(0);

        $this->assertTrue($money->isZero());
    }

    public function testMin()
    {
        $money = Money::min(
            Money::NGN(13764),
            Money::NGN(3535),
            Money::NGN(5455),
            Money::NGN(44345),
            Money::NGN(13765),
        );

        $this->assertSame(3535, $money->getInt());
    }

    public function testMax()
    {
        $money = Money::max(
            Money::NGN(13764),
            Money::NGN(3535),
            Money::NGN(5455),
            Money::NGN(10345),
            Money::NGN(13765),
        );

        $this->assertSame(13765, $money->getInt());
    }

    public function testAverage()
    {
        $money = Money::avg(
            Money::NGN(300),
            Money::NGN(434),
            Money::NGN(345),
            Money::NGN(872),
            Money::NGN(700),
        );

        $this->assertSame(530, $money->getInt());
    }

    public function testAddAndRemoveCurrency()
    {
        Money::currencies(
            Currency::new('WAL', '&', 'Wale', 'Ola', 100, 419)
        );

        /**
         * @var Money
         */
        $money = Money::WAL(100000);

        $this->assertSame('WAL', $money->getCurrency()->getCode());
        $this->assertSame(2, $money->getCurrency()->subunitLength());

        $currency = Money::currency('WAL');
        $this->assertSame('WAL', $currency->getCode());
        $this->assertSame(2, $currency->subunitLength());

        Money::removeCurrency('WAL');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[WAL] currency not supported.'
        );
        Money::WAL(100000);
    }

    public function testRemoveAllCurrency()
    {
        Money::removeAllCurrency();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            '[NGN] currency not supported.'
        );
        Money::NGN(100000);
    }

    public function testIntlFormatter()
    {
        $this->setUpCurrencies();

        $formatter = new IntlMoneyFormatter(
            new \NumberFormatter('en_US', \NumberFormatter::CURRENCY)
        );
        $money = Money::USD(250000);

        $this->assertSame('$2,500.00', $formatter->format($money, $money->getCurrency()));
    }

    public function testMutatbleMoney()
    {
        $money = Money::NGN(100000);
        $afterAdd1 = $money->mutable()->add(Money::NGN(200000));
        $afterSubtract1 = $money->subtract(Money::NGN(100000));
        $afterMultiply1 = $money->multiply(2);
        $afterDivide1 = $money->divide(2);
        $negativeValue = ($afterNegative1 = $money->negative())->getInt();
        $afterAbsolute1 = $money->absolute();

        $this->assertSame($money, $afterAdd1);
        $this->assertSame($money, $afterSubtract1);
        $this->assertSame($money, $afterMultiply1);
        $this->assertSame($money, $afterDivide1);
        $this->assertSame($money, $afterNegative1);
        $this->assertSame($money, $afterAbsolute1);
        $this->assertSame(-200000, $negativeValue);

        $afterAdd2 = $money->immutable()->add(Money::NGN(200000));
        $afterSubtract2 = $money->subtract(Money::NGN(100000));
        $afterMultiply2 = $money->multiply(2);
        $afterDivide2 = $money->divide(2);
        $afterNegative2 = $money->negative();
        $afterAbsolute2 = $money->absolute();

        $this->assertNotSame($money, $afterAdd2);
        $this->assertNotSame($money, $afterSubtract2);
        $this->assertNotSame($money, $afterMultiply2);
        $this->assertNotSame($money, $afterDivide2);
        $this->assertNotSame($money, $afterNegative2);
        $this->assertNotSame($money, $afterAbsolute2);

        $this->assertSame(400000, $afterAdd2->getInt());
        $this->assertSame(100000, $afterSubtract2->getInt());
        $this->assertSame(400000, $afterMultiply2->getInt());
        $this->assertSame(100000, $afterDivide2->getInt());
        $this->assertSame(-200000, $afterNegative2->getInt());
        $this->assertSame(200000, $afterAbsolute2->getInt());

        $this->assertSame(200000, $money->getInt());
    }
}
