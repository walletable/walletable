<?php

namespace Walletable\Money\Formatter;

use Walletable\Money\Currency;
use Walletable\Money\Money;

/**
 * Formats a Money object using intl extension.
 *
 * @author Frederik Bosch <f.bosch@genkgo.nl>
 */
class IntlMoneyFormatter implements MoneyFormatter
{
    /**
     * @var \NumberFormatter
     */
    private $formatter;

    public function __construct(\NumberFormatter $formatter)
    {
        $this->formatter = $formatter;
    }

    /**
     * {@inheritdoc}
     */
    public function format(Money $money, Currency $currency)
    {
        $formatted = $money->whole();

        return str_replace(
            ".00",
            '',
            str_replace(
                "\u{00a0}",
                '',
                str_replace(
                    $money->getCurrency()->getCode(),
                    $money->getCurrency()->getSymbol(),
                    $this->formatter->formatCurrency(
                        $formatted,
                        $money->getCurrency()->getCode()
                    )
                )
            )
        );
    }
}
