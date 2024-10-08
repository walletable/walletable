<?php

namespace Walletable\Money;

use Closure;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Walletable\Money\Calculator\CalculatorInterface;
use Walletable\Money\Calculator\PhpCalculator;
use Walletable\Money\Traits\HasFormatters;
use Walletable\Money\Traits\MoneyFactory;

/**
 * Money Value Object.
 *
 * @author Mathias Verraes
 *
 * @psalm-immutable
 */
class Money implements \JsonSerializable
{
    use Macroable, MoneyFactory {
        Macroable::__callStatic as macroCallStatic;
        MoneyFactory::__callStatic as factoryCallStatic;
    }
    use HasFormatters;

    public const ROUND_HALF_UP = PHP_ROUND_HALF_UP;

    public const ROUND_HALF_DOWN = PHP_ROUND_HALF_DOWN;

    public const ROUND_HALF_EVEN = PHP_ROUND_HALF_EVEN;

    public const ROUND_HALF_ODD = PHP_ROUND_HALF_ODD;

    public const ROUND_UP = 5;

    public const ROUND_DOWN = 6;

    public const ROUND_HALF_POSITIVE_INFINITY = 7;

    public const ROUND_HALF_NEGATIVE_INFINITY = 8;

    /**
     * Internal value.
     *
     * @var string
     */
    private $amount;

    /**
     * @var Currency
     */
    private $currency;

    /**
     * Supported currency
     * @var array<string=>Currency>
     */
    private static $currencies;

    /**
     * @var \Walletable\Money\Calculator\CalculatorInterface
     */
    private static $calculator;

    /**
     * @var array
     */
    private static $calculators = [
        PhpCalculator::class,
    ];

    /**
     * Determines if the internal value can be changed.
     *
     * @var bool
     */
    private $immutable = true;

    /**
     * @param int|string $amount Amount, expressed in the smallest units of $currency (eg cents)
     *
     * @throws \InvalidArgumentException If amount is not integer
     */
    public function __construct($amount, Currency $currency)
    {
        if (filter_var($amount, FILTER_VALIDATE_INT) === false) {
            $numberFromString = Number::fromString($amount);
            if (!$numberFromString->isInteger()) {
                throw new \InvalidArgumentException('Amount must be an integer(ish) value');
            }

            $amount = $numberFromString->getIntegerPart();
        }

        $this->amount = (string) $amount;
        $this->currency = $currency;
    }

    /**
     * Returns a new Money instance based on the current one using the Currency.
     *
     * @param int|string $amount
     *
     * @return Money
     *
     * @throws \InvalidArgumentException If amount is not integer
     */
    private function newInstance($amount)
    {
        return new static($amount, $this->currency);
    }

    /**
     * Returns a new Money instance based on the current one using the Currency.
     *
     * @param int|string $amount
     *
     * @return Money
     *
     * @throws \InvalidArgumentException If amount is not integer
     */
    private function newInstanceIfImmutable($amount)
    {
        if ($this->isImmutable()) {
            return new static($amount, $this->currency);
        } else {
            $this->amount = (string)$amount;
        }

        return $this;
    }

    /**
     * Checks whether a Money has the same Currency as this.
     *
     * @return bool
     */
    public function isSameCurrency(Money $other)
    {
        return $this->currency->equals($other->currency);
    }

    /**
     * Asserts that a Money has the same currency as this.
     *
     * @throws \InvalidArgumentException If $other has a different currency
     */
    private function assertSameCurrency(Money $other)
    {
        if (!$this->isSameCurrency($other)) {
            throw new \InvalidArgumentException('Currencies must be identical');
        }
    }

    /**
     * Checks whether the value represented by this object equals to the other.
     *
     * @return bool
     */
    public function equals(Money $other)
    {
        return $this->isSameCurrency($other) && $this->amount === $other->amount;
    }

    /**
     * Returns an integer less than, equal to, or greater than zero
     * if the value of this object is considered to be respectively
     * less than, equal to, or greater than the other.
     *
     * @return int
     */
    public function compare(Money $other)
    {
        $this->assertSameCurrency($other);

        return $this->getCalculator()->compare($this->amount, $other->amount);
    }

    /**
     * Checks whether the value represented by this object is greater than the other.
     *
     * @return bool
     */
    public function greaterThan(Money $other)
    {
        return $this->compare($other) > 0;
    }

    /**
     * @param \Walletable\Money\Money $other
     *
     * @return bool
     */
    public function greaterThanOrEqual(Money $other)
    {
        return $this->compare($other) >= 0;
    }

    /**
     * Checks whether the value represented by this object is less than the other.
     *
     * @return bool
     */
    public function lessThan(Money $other)
    {
        return $this->compare($other) < 0;
    }

    /**
     * @param \Walletable\Money\Money $other
     *
     * @return bool
     */
    public function lessThanOrEqual(Money $other)
    {
        return $this->compare($other) <= 0;
    }

    /**
     * Returns the value represented by this object.
     *
     * @deprecated deprecated since version 0.3
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Returns the value represented by this object as integer
     *
     * @deprecated deprecated since version 0.3
     *
     * @return int
     */
    public function getInt()
    {
        return (int)$this->getAmount();
    }

    /**
     * Returns the value represented by this object.
     *
     * @return string
     */
    public function value()
    {
        return $this->amount;
    }

    /**
     * Returns the value represented by this object as integer
     *
     * @return int
     */
    public function integer()
    {
        return (int)$this->value();
    }

    /**
     * Get the amount in major unit
     *
     * @return string
     */
    public function whole()
    {
        $valueBase = $this->value();
        $negative = false;

        if ($valueBase[0] === '-') {
            $negative = true;
            $valueBase = substr($valueBase, 1);
        }

        $subunit = $this->currency->subunitLength();
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

        return str_replace(".00", '', $formatted);
    }

    /**
     * Returns the currency of this object.
     *
     * @return Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Returns a new Money object that represents
     * the sum of this and an other Money object.
     *
     * @param Money ...$addends
     *
     * @return Money
     */
    public function add(Money ...$addends)
    {
        $amount = $this->amount;
        $calculator = $this->getCalculator();

        foreach ($addends as $addend) {
            $this->assertSameCurrency($addend);

            $amount = $calculator->add($amount, $addend->amount);
        }

        return $this->newInstanceIfImmutable($amount);
    }

    /**
     * Returns a new Money object that represents
     * the difference of this and an other Money object.
     *
     * @param Money ...$subtrahends
     *
     * @return Money
     *
     * @psalm-pure
     */
    public function subtract(Money ...$subtrahends)
    {
        $amount = $this->amount;
        $calculator = $this->getCalculator();

        foreach ($subtrahends as $subtrahend) {
            $this->assertSameCurrency($subtrahend);

            $amount = $calculator->subtract($amount, $subtrahend->amount);
        }

        return $this->newInstanceIfImmutable($amount);
    }

    /**
     * Asserts that the operand is integer or float.
     *
     * @param float|int|string $operand
     *
     * @throws \InvalidArgumentException If $operand is neither integer nor float
     */
    private function assertOperand($operand)
    {
        if (!is_numeric($operand)) {
            throw new \InvalidArgumentException(sprintf(
                'Operand should be a numeric value, "%s" given.',
                is_object($operand) ? get_class($operand) : gettype($operand)
            ));
        }
    }

    /**
     * Asserts that rounding mode is a valid integer value.
     *
     * @param int $roundingMode
     *
     * @throws \InvalidArgumentException If $roundingMode is not valid
     */
    private function assertRoundingMode($roundingMode)
    {
        if (
            !in_array(
                $roundingMode,
                [
                    self::ROUND_HALF_DOWN, self::ROUND_HALF_EVEN, self::ROUND_HALF_ODD,
                    self::ROUND_HALF_UP, self::ROUND_UP, self::ROUND_DOWN,
                    self::ROUND_HALF_POSITIVE_INFINITY, self::ROUND_HALF_NEGATIVE_INFINITY,
                ],
                true
            )
        ) {
            throw new \InvalidArgumentException(
                'Rounding mode should be Money::ROUND_HALF_DOWN | ' .
                'Money::ROUND_HALF_EVEN | Money::ROUND_HALF_ODD | ' .
                'Money::ROUND_HALF_UP | Money::ROUND_UP | Money::ROUND_DOWN' .
                'Money::ROUND_HALF_POSITIVE_INFINITY | Money::ROUND_HALF_NEGATIVE_INFINITY'
            );
        }
    }

    /**
     * Returns a new Money object that represents
     * the multiplied value by the given factor.
     *
     * @param float|int|string $multiplier
     * @param int              $roundingMode
     *
     * @return Money
     */
    public function multiply($multiplier, $roundingMode = self::ROUND_HALF_UP)
    {
        $this->assertOperand($multiplier);
        $this->assertRoundingMode($roundingMode);

        $product = $this->round($this->getCalculator()->multiply($this->amount, $multiplier), $roundingMode);

        return $this->newInstanceIfImmutable($product);
    }

    /**
     * Returns a new Money object that represents
     * the divided value by the given factor.
     *
     * @param float|int|string $divisor
     * @param int              $roundingMode
     *
     * @return Money
     */
    public function divide($divisor, $roundingMode = self::ROUND_HALF_UP)
    {
        $this->assertOperand($divisor);
        $this->assertRoundingMode($roundingMode);

        $divisor = (string) Number::fromNumber($divisor);

        if ($this->getCalculator()->compare($divisor, '0') === 0) {
            throw new \InvalidArgumentException('Division by zero');
        }

        $quotient = $this->round($this->getCalculator()->divide($this->amount, $divisor), $roundingMode);

        return $this->newInstanceIfImmutable($quotient);
    }

    /**
     * Returns a new Money object that represents
     * the remainder after dividing the value by
     * the given factor.
     *
     * @return Money
     */
    public function mod(Money $divisor)
    {
        $this->assertSameCurrency($divisor);

        return new static($this->getCalculator()->mod($this->amount, $divisor->amount), $this->currency);
    }

    /**
     * Allocate the money according to a list of ratios.
     *
     * @return Money[]
     */
    public function allocate(array $ratios)
    {
        if (count($ratios) === 0) {
            throw new \InvalidArgumentException('Cannot allocate to none, ratios cannot be an empty array');
        }

        $remainder = $this->amount;
        $results = [];
        $total = array_sum($ratios);

        if ($total <= 0) {
            throw new \InvalidArgumentException('Cannot allocate to none, sum of ratios must be greater than zero');
        }

        foreach ($ratios as $key => $ratio) {
            if ($ratio < 0) {
                throw new \InvalidArgumentException('Cannot allocate to none, ratio must be zero or positive');
            }
            $share = $this->getCalculator()->share($this->amount, $ratio, $total);
            $results[$key] = $this->newInstance($share);
            $remainder = $this->getCalculator()->subtract($remainder, $share);
        }

        if ($this->getCalculator()->compare($remainder, '0') === 0) {
            return $results;
        }

        $fractions = array_map(function ($ratio) use ($total) {
            $share = ($ratio / $total) * $this->amount;

            return $share - floor($share);
        }, $ratios);

        while ($this->getCalculator()->compare($remainder, '0') > 0) {
            $index = !empty($fractions) ? array_keys($fractions, max($fractions))[0] : 0;
            $results[$index]->amount = $this->getCalculator()->add($results[$index]->amount, '1');
            $remainder = $this->getCalculator()->subtract($remainder, '1');
            unset($fractions[$index]);
        }

        return $results;
    }

    /**
     * Allocate the money among N targets.
     *
     * @param int $n
     *
     * @return Money[]
     *
     * @throws \InvalidArgumentException If number of targets is not an integer
     */
    public function allocateTo($n)
    {
        if (!is_int($n)) {
            throw new \InvalidArgumentException('Number of targets must be an integer');
        }

        if ($n <= 0) {
            throw new \InvalidArgumentException('Cannot allocate to none, target must be greater than zero');
        }

        return $this->allocate(array_fill(0, $n, 1));
    }

    /**
     * @return string
     */
    public function ratioOf(Money $money)
    {
        if ($money->isZero()) {
            throw new \InvalidArgumentException('Cannot calculate a ratio of zero');
        }

        return $this->getCalculator()->divide($this->amount, $money->amount);
    }

    /**
     * @param string $amount
     * @param int    $rounding_mode
     *
     * @return string
     */
    private function round($amount, $rounding_mode)
    {
        $this->assertRoundingMode($rounding_mode);

        if ($rounding_mode === self::ROUND_UP) {
            return $this->getCalculator()->ceil($amount);
        }

        if ($rounding_mode === self::ROUND_DOWN) {
            return $this->getCalculator()->floor($amount);
        }

        return $this->getCalculator()->round($amount, $rounding_mode);
    }

    /**
     * @return Money
     */
    public function absolute()
    {
        return $this->newInstanceIfImmutable($this->getCalculator()->absolute($this->amount));
    }

    /**
     * @return Money
     */
    public function negative()
    {
        return $this->newInstanceIfImmutable($this->getCalculator()->subtract(0, $this->amount));
    }

    /**
     * Make the money object immutatble
     * @return $this
     */
    public function immutable()
    {
        $this->immutable = true;

        return $this;
    }

    /**
     * Make the money object mutatble
     * @return $this
     */
    public function mutable()
    {
        $this->immutable = false;

        return $this;
    }

    /**
     * Checks if the value represented by this object is zero.
     *
     * @return bool
     */
    public function isZero()
    {
        return $this->getCalculator()->compare($this->amount, 0) === 0;
    }

    /**
     * Checks if the internal value can be changed.
     *
     * @return bool
     */
    public function isImmutable()
    {
        return $this->immutable;
    }

    /**
     * Checks if the value represented by this object is positive.
     *
     * @return bool
     */
    public function isPositive()
    {
        return $this->getCalculator()->compare($this->amount, 0) > 0;
    }

    /**
     * Checks if the value represented by this object is negative.
     *
     * @return bool
     */
    public function isNegative()
    {
        return $this->getCalculator()->compare($this->amount, 0) < 0;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'amount' => $this->amount,
            'display' => $this->display(),
            'whole' => $this->whole(),
            'symbol' => $this->currency->getSymbol(),
            'currency' => $this->currency->jsonSerialize(),
        ];
    }

    /**
     * Cast the object to json using closure
     *
     * @param \Clossure
     * @return string
     */
    public function json(Closure $callback = null)
    {
        return ($callback instanceof Closure) ?
            $callback($this, $this->jsonSerialize()) :
            $this->jsonSerialize();
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function min(self $first, self ...$collection)
    {
        $min = $first;

        foreach ($collection as $money) {
            if ($money->lessThan($min)) {
                $min = $money;
            }
        }

        return $min;
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function max(self $first, self ...$collection)
    {
        $max = $first;

        foreach ($collection as $money) {
            if ($money->greaterThan($max)) {
                $max = $money;
            }
        }

        return $max;
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function sum(self $first, self ...$collection)
    {
        return $first->add(...$collection);
    }

    /**
     * @param Money $first
     * @param Money ...$collection
     *
     * @return Money
     *
     * @psalm-pure
     */
    public static function avg(self $first, self ...$collection)
    {
        return $first->add(...$collection)->divide(func_num_args());
    }

    /**
     * @param string $calculator
     */
    public static function registerCalculator($calculator)
    {
        if (is_a($calculator, CalculatorInterface::class, true) === false) {
            throw new \InvalidArgumentException('Calculator must implement ' . CalculatorInterface::class);
        }

        array_unshift(self::$calculators, $calculator);
    }

    /**
     * @return Calculator
     *
     * @throws \RuntimeException If cannot find calculator for money calculations
     */
    private static function initializeCalculator()
    {
        $calculators = self::$calculators;

        foreach ($calculators as $calculator) {
            /** @var Calculator $calculator */
            if ($calculator::supported()) {
                return new $calculator();
            }
        }

        throw new \RuntimeException('Cannot find calculator for money calculations');
    }

    /**
     * @return CalculatorInterface
     */
    private function getCalculator(): CalculatorInterface
    {
        if (null === self::$calculator) {
            self::$calculator = self::initializeCalculator();
        }

        return self::$calculator;
    }

    /**
     * Display object using a formatter
     *
     * @param string $formatter
     */
    public function display(string $formatter = 'intl'): string
    {
        $formatter = static::formatter($formatter);

        return $formatter->format($this, $this->getCurrency());
    }

    /**
     * Cast object to string
     *
     * @param string $formatter
     */
    public function __toString()
    {
        return $this->display();
    }

    /**
     * Add supported currencies
     *
     * @param Currency ...$currencies
     * @return void
     */
    public static function currencies(Currency ...$currencies)
    {
        foreach ($currencies as $currency) {
            static::$currencies[$currency->getCode()] = $currency;
        }
    }

    /**
     * Check if currency is supported
     *
     * @param Currency|string $currency
     * @return boolean
     */
    public static function hasCurrency($currency)
    {
        if (!is_string($currency) && !($currency instanceof Currency)) {
            throw new InvalidArgumentException(
                sprintf('Argument #1 can either be string or instance of %s', Currency::class)
            );
        } elseif ($currency instanceof Currency) {
            $currency = $currency->getCode();
        }

        return isset(static::$currencies[$currency]);
    }

    /**
     * Get currency instance
     *
     * @param string $currency
     * @return Currency|null
     */
    public static function currency($currency)
    {
        if (!isset(static::$currencies[$currency])) {
            throw new InvalidArgumentException(sprintf('[%s] currency not supported.', $currency));
        }

        return static::$currencies[$currency];
    }

    /**
     * Remove currency
     *
     * @param Currency|string $currency
     * @return void
     */
    public static function removeCurrency($currency)
    {
        if (!is_string($currency) && !($currency instanceof Currency)) {
            throw new InvalidArgumentException(
                sprintf('Argument #1 can either be string or instance of %s', Currency::class)
            );
        } elseif ($currency instanceof Currency) {
            $currency = $currency->getCode();
        }

        if (isset(static::$currencies[$currency])) {
            unset(static::$currencies[$currency]);
        }
    }

    /**
     * Remove all currency
     *
     * @return void
     */
    public static function removeAllCurrency()
    {
        static::$currencies = [];
    }

    /**
     * Resolve the trait of maigic methods
     *
     * @param string          $method
     * @param array{int=>mixed} $arguments
     *
     * @throws InvalidArgumentException.
     *
     */
    public static function __callStatic(string $method, array $arguments): Money
    {
        if (static::hasMacro($method)) {
            return static::macroCallStatic($method, $arguments);
        }

        return static::factoryCallStatic($method, $arguments);
    }
}
