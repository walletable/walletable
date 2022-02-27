<?php

namespace Walletable\Money;

use Illuminate\Support\Traits\ForwardsCalls;

class Currencies
{
    use ForwardsCalls;

    /**
     * Currencys collection
     *
     * @var \Illuminate\Support\Collection
     */
    protected $currencies;

    public function __construct(?Currency ...$currencies)
    {
        $newCurrencies = [];

        foreach ($currencies as $currency) {
            $newCurrencies[$currency->getCode()] = $currency;
        }

        $this->currencies = \collect($newCurrencies ?? []);
    }

    /**
     * Static Collecting method
     *
     * @param \Walletable\Money\Currency |null ...$currencies
     */
    public static function create(Currency ...$currencies)
    {
        return new static(...$currencies);
    }

    /**
     * Add new currency
     *
     * @param \Walletable\Money\Currency $currency
     * @return self
     */
    public function add(Currency $currency)
    {
        $this->currencies->add($currency);
        return $this;
    }

    /**
     * Get currency by code
     *
     * @param string $code
     * @return \Walletable\Money\Currency
     */
    public function get(string $code)
    {
        return $this->currencies[$code] ?? null;
    }

    /**
     * Map method calls to the collection
     *
     * @param string $method
     * @param array $parameters
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->currencies, $method, $parameters);
    }
}
