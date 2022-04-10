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
        $this->expectExceptionMessage('[NGN] currency not supported.');
        Money::NGN(100000);
    }
}
