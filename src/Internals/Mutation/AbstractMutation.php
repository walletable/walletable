<?php

namespace Walletable\Internals\Mutation;

/**
 * @method void after()
 * @method void before()
 */
abstract class AbstractMutation implements MutationInterface
{
    /**
     * The mutation value
     *
     * @var mixed
     */
    protected $value;

    /**
     * The mutation extras
     *
     * @var array
     */
    protected $extras = [];

    /**
     * Stop Propagation
     *
     * @var boolean
     */
    protected $stop = false;

    /**
     * Before Propagation
     *
     * @var callable
     */
    protected $before;

    /**
     * After Propagation
     *
     * @var callable
     */
    protected $after;

    /**
     * Get the mutation value.
     *
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    /**
     * Set the mutation value.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get the mutation extras.
     *
     * @return array
     */
    public function extras(): array
    {
        return $this->extras;
    }

    /**
     * Set the mutation ectras.
     *
     * @param array $extras
     * @return $this
     */
    public function setExtras(array $extras)
    {
        $this->extras = $extras;
        return $this;
    }

    /**
     * Check if the mutation has ectras.
     *
     * @param array $extras
     * @return $this
     */
    public function hasExtras(): bool
    {
        return !empty($this->extras);
    }

    /**
     * Stop mutation propagation.
     *
     * @return $this
     */
    public function stop()
    {
        $this->stop = true;

        return $this;
    }

    /**
     * Resume mutation propagation.
     *
     * @return $this
     */
    public function resume()
    {
        $this->stop = false;

        return $this;
    }

    /**
     * Check whether propagation was stopped.
     *
     * @return bool
     */
    public function stopped()
    {
        return $this->stop;
    }

    /**
     * After the value is mutated
     *
     * @param callable $callback
     * @return $this
     */
    public function setAfter(callable $callback): MutationInterface
    {
        $this->after = $callback;
        return $this;
    }

    /**
     * Before the value is mutated
     *
     * @param callable $callback
     * @return $this
     */
    public function setBefore(callable $callback): MutationInterface
    {
        $this->before = $callback;
        return $this;
    }

    /**
     * Check if after callback exists
     *
     * @return bool
     */
    public function hasAfter(): bool
    {
        return isset($this->after);
    }

    /**
     * Check if before callback exists
     *
     * @return bool
     */
    public function hasBefore(): bool
    {
        return isset($this->before);
    }

    /**
     * Invoke after callback
     *
     * @return $this
     */
    public function invokeAfter(): MutationInterface
    {
        call_user_func_array($this->after, [$this]);
        return $this;
    }

    /**
     * Invoke before callback
     *
     * @return $this
     */
    public function invokeBefore(): MutationInterface
    {
        call_user_func_array($this->before, [$this]);
        return $this;
    }

    /**
     * Get the mutation name.
     *
     * @return string
     */
    public function getName()
    {
        return static::class;
    }
}
