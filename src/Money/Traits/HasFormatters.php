<?php

namespace Walletable\Money\Traits;

use Closure;
use Exception;
use InvalidArgumentException;
use Walletable\Money\Formatter\MoneyFormatter;

/**
 * Add formatters to the money class
 */
trait HasFormatters
{
    /**
     * Unresolved formatters
     *
     * @var array
     */
    protected static $formatterResolvers = [];

    /**
     * Formatters
     *
     * @var array
     */
    protected static $formatters = [];

    /**
     * Load formatter to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $formatter
     *
     * @return \Walletable\Money\Formatter\MoneyFormatter|void
     */
    public static function formatter(string $name, $formatter = null)
    {
        if (
            !is_null($formatter) &&
            !is_string($formatter) &&
            !($formatter instanceof Closure)
        ) {
            throw new InvalidArgumentException('A formatter can only be resolved through class name or closure');
        }

        if (!is_null($formatter)) {
            if (
                is_string($formatter) &&
                !(class_exists($formatter) && is_subclass_of($formatter, MoneyFormatter::class))
            ) {
                throw new Exception('Formatter class must implement ' . MoneyFormatter::class);
            }

            static::$formatterResolvers[$name] = $formatter;
        } else {
            return static::getResolvedFormatter($name);
        }
    }

    /**
     * Resolve or get an already resolved formatter instance
     *
     * @param string $name
     */
    protected static function getResolvedFormatter(string $name)
    {
        if (!isset(static::$formatterResolvers[$name])) {
            throw new Exception("\"$name\" not found as money formatter");
        }

        if (!isset(static::$formatters[$name])) {
            if (($resolver = static::$formatterResolvers[$name]) instanceof Closure) {
                $formatter = static::resolveFormatterFromClosure($resolver);
            } else {
                $formatter = static::resolveFormatterFromClass($resolver);
            }
            return static::$formatters[$name] = $formatter;
        } else {
            return static::$formatters[$name];
        }
    }

    /**
     * Resolve a formatter from closure
     *
     * @param Closure $resolver
     *
     * @return \Walletable\Money\Formatter\MoneyFormatter
     */
    protected static function resolveFormatterFromClosure(Closure $resolver): MoneyFormatter
    {
        if (!($formatter = app()->call($resolver)) instanceof MoneyFormatter) {
            throw new Exception('Closure resolver must return an instance of ' . MoneyFormatter::class);
        }

        return $formatter;
    }

    /**
     * Resolve a formatter from string
     *
     * @param string $resolver
     *
     * @return \Walletable\Money\Formatter\MoneyFormatter
     */
    protected static function resolveFormatterFromClass(string $resolver): MoneyFormatter
    {
        return app()->make($resolver);
    }
}
