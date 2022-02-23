<?php

namespace Walletable\Money\Formatter;

use Walletable\Money\Currency;
use Walletable\Money\Money;

/**
 * Formats Money objects.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
interface MoneyFormatter
{
    /**
     * Formats a Money object as string.
     *
     * @param Money $money
     *
     * @return string
     *
     * Exception\FormatterException
     */
    public function format(Money $money, Currency $currency);
}
