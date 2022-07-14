<?php

namespace Walletable\Internals;

use Walletable\Contracts\ArgumentBag;
use Walletable\Exceptions\InvalidArgumentException;

class Argument
{
    /**
     * Command with arguments
     *
     * @var \Walletable\Contracts\ArgumentBag
     */
    public $argumentBag;

    /**
     * Key of argument in array
     *
     * @var int
     */
    public $key;

    /**
     * Creates new instance from given command and key
     *
     * @param AbstractCommand $argumentBag
     * @param int             $key
     */
    public function __construct(ArgumentBag $argumentBag, $key = 0)
    {
        $this->argumentBag = $argumentBag;
        $this->key = $key;
    }

    /**
     * Get the name of the bag
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->argumentBag->getName();
    }

    /**
     * Returns value of current argument
     *
     * @param  mixed $default
     * @return mixed
     */
    public function value($default = null)
    {
        if ($value = $this->argumentBag->getKeyValue($this->key)) {
            return $value;
        }

        return $default;
    }

    /**
     * Defines current argument as required
     *
     * @return self
     */
    public function required(): self
    {
        if (!$this->argumentBag->keyExists($this->key)) {
            throw new InvalidArgumentException(
                sprintf("Missing argument %d for %s", $this->key + 1, $this->getName())
            );
        }

        return $this;
    }

    /**
     * Defines current argument as not empty
     *
     * @return self
     */
    public function notEmpty(): self
    {
        if (
            $this->argumentBag->keyExists($this->key) &&
            is_null($this->argumentBag->getKeyValue($this->key))
        ) {
            throw new InvalidArgumentException(
                sprintf("Empty argument %d for %s", $this->key + 1, $this->getName())
            );
        }

        return $this;
    }

    /**
     * Defines current argument is a of a class or subclass
     *
     * @param string $class
     * @return self
     */
    public function isA(string $class): self
    {
        $value = $this->value();

        if (is_object($value) && is_a($value, $class)) {
            return $this;
        }

        throw new InvalidArgumentException(
            sprintf(
                "Argument %d must be an instance of %s but %s given for %s",
                $this->key + 1,
                $class,
                gettype($value),
                $this->getName()
            )
        );
    }

    /**
     * Determines that current argument must be of given type
     *
     * @return self
     */
    public function type($type): self
    {
        $valid = true;
        $value = $this->value();

        if ($value === null) {
            return $this;
        }

        switch (strtolower($type)) {
            case 'bool':
            case 'boolean':
                $valid = \is_bool($value);
                $message = '%s accepts only boolean values as argument %d.';
                break;
            case 'int':
            case 'integer':
                $valid = \is_int($value);
                $message = '%s accepts only integer values as argument %d.';
                break;
            case 'num':
            case 'numeric':
                $valid = is_numeric($value);
                $message = '%s accepts only numeric values as argument %d.';
                break;
            case 'str':
            case 'string':
                $valid = \is_string($value);
                $message = '%s accepts only string values as argument %d.';
                break;
            case 'array':
                $valid = \is_array($value);
                $message = '%s accepts only array as argument %d.';
                break;
            case 'closure':
                $valid = is_a($value, '\Closure');
                $message = '%s accepts only Closure as argument %d.';
                break;
            case 'digit':
                $valid = $this->isDigit($value);
                $message = '%s accepts only integer values as argument %d.';
                break;
        }

        if (! $valid) {
            $argumentBagName = $this->getName();
            $argument = $this->key + 1;

            if (isset($message)) {
                $message = sprintf($message, $argumentBagName, $argument);
            } else {
                $message = sprintf('Missing argument for %d.', $argument);
            }

            throw new InvalidArgumentException(
                $message
            );
        }

        return $this;
    }

    /**
     * Determines that current argument value must be numeric between given values
     *
     * @return self
     */
    public function between($x, $y)
    {
        $value = $this->type('numeric')->value();

        if (is_null($value)) {
            return $this;
        }

        $alpha = min($x, $y);
        $omega = max($x, $y);

        if ($value < $alpha || $value > $omega) {
            throw new InvalidArgumentException(
                sprintf('Argument %d must be between %s and %s.', $this->key, $x, $y)
            );
        }

        return $this;
    }

    /**
     * Determines that current argument must be over a minimum value
     *
     * @return self
     */
    public function min($value)
    {
        $v = $this->type('numeric')->value();

        if (is_null($v)) {
            return $this;
        }

        if ($v < $value) {
            throw new InvalidArgumentException(
                sprintf('Argument %d must be at least %s.', $this->key, $value)
            );
        }

        return $this;
    }

    /**
     * Determines that current argument must be under a maxiumum value
     *
     * @return self
     */
    public function max($value)
    {
        $v = $this->type('numeric')->value();

        if (is_null($v)) {
            return $this;
        }

        if ($v > $value) {
            throw new InvalidArgumentException(
                sprintf('Argument %d may not be greater than %s.', $this->key, $value)
            );
        }

        return $this;
    }

    /**
     * Checks if value is "PHP" integer (120 but also 120.0)
     *
     * @param  mixed $value
     * @return boolean
     */
    private function isDigit($value)
    {
        return is_numeric($value) ? intval($value) == $value : false;
    }
}
