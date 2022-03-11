<?php

namespace Walletable\Money;

use Illuminate\Support\Traits\Macroable;

/**
 * Currency Value Object.
 *
 * Holds Currency specific data.
 *
 * @author Mathias Verraes
 *
 * @psalm-immutable
 */
class Currency implements \JsonSerializable
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * Currency code.
     *
     * @var string
     */
    private $code;

    /**
     * Currency symbol.
     *
     * @var string
     */
    private $symbol;

    /**
     * Currency name.
     *
     * @var string
     */
    private $name;

    /**
     * Currency`s smaller unit name.
     *
     * @var string
     */
    private $subunit;

    /**
     * How many smaller units make one unit.
     *
     * @var int
     */
    private $per;

    /**
     * Currency numeric code.
     *
     * @var int
     */
    private $numeric;

    /**
     * @param string $code
     * @param string $symbol
     * @param string $name
     * @param string $subunit
     * @param int $per = null
     * @param int $number = null
     *
     */
    public function __construct(
        string $code,
        string $symbol,
        string $name,
        string $subunit,
        int $per = null,
        int $numeric = null
    ) {
        if ($code === '') {
            throw new \InvalidArgumentException('Currency code should not be empty string');
        }

        $this->code = $code;
        $this->symbol = $symbol;
        $this->name = $name;
        $this->subunit = $subunit;
        $this->per = $per;
        $this->numeric = $numeric;
    }

    /**
     * Create a new currency object
     *
     * @param string $code
     * @param string $symbol
     * @param string $name
     * @param string $subunit
     * @param int $per = null
     * @param int $number = null
     *
     * @return self
     */
    public static function new(
        string $code,
        string $symbol,
        string $name,
        string $subunit,
        int $per = null,
        int $numeric = null
    ): self {
        return new static($code, $symbol, $name, $subunit, $per, $numeric);
    }

    /**
     * Returns the currency code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Checks whether this currency is the same as an other.
     *
     * @return bool
     */
    public function equals(Currency $other)
    {
        return $this->code === $other->code;
    }

    /**
     * Checks whether this currency is available in the passed context.
     *
     * @return bool
     */
    /* public function isAvailableWithin(Currencies $currencies)
    {
        return $currencies->contains($this);
    } */

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->code;
    }

    /**
     * Handle dynamic method calls into the object.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (substr($method, 0, 3) === 'get') {
            return $this->{strtolower(substr($method, 3, strlen($method)))};
        }

        return $this->macroCall($method, $parameters);
    }

    /**
     * Get the subunit length of the currency
     *
     * @return int
     */
    public function subunitLength()
    {
        return strlen(substr((string)$this->per, 1));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->code;
    }
}
