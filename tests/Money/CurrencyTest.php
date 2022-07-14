<?php

namespace Walletable\Tests\Money;

use Walletable\Money\Currency;
use Walletable\Money\Money;
use Walletable\Tests\TestBench as TestCase;

class CurrencyTest extends TestCase
{
    public function testCurrency()
    {
        $this->setUpCurrencies();
        $currency = Money::currency('NGN');

        $this->assertSame('NGN', $currency->getCode());
        $this->assertSame(2, $currency->subunitLength());

        $currency = Currency::new('USD', '$', 'Dollar', 'Cent', 100, 840);

        $this->assertSame('USD', $currency->getCode());
        $this->assertSame(2, $currency->subunitLength());
    }

    public function testCurrencyEquals()
    {
        $currency = Money::currency('NGN');
        $currency2 = Money::currency('USD');

        $this->assertNotTrue($currency->equals($currency2));
        $this->assertTrue($currency->equals($currency));
        $this->assertSame('NGN', (string)$currency);
    }
}
