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
        $valueBase = $money->value();
        $negative = false;

        if ($valueBase[0] === '-') {
            $negative = true;
            $valueBase = substr($valueBase, 1);
        }

        $subunit = $currency->subunitLength();
        $valueLength = strlen($valueBase);

        if ($valueLength > $subunit) {
            $formatted = substr($valueBase, 0, $valueLength - $subunit);
            $decimalDigits = substr($valueBase, $valueLength - $subunit);

            if (strlen($decimalDigits) > 0) {
                $formatted .= '.' . $decimalDigits;
            }
        } else {
            $formatted = '0.' . str_pad('', $subunit - $valueLength, '0') . $valueBase;
        }

        if ($negative === true) {
            $formatted = '-' . $formatted;
        }

        return str_replace(
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
        );
    }
}
