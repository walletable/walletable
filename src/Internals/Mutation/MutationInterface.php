<?php

namespace Walletable\Internals\Mutation;

interface MutationInterface
{
    /**
     * Get the mutation value.
     *
     * @return mixed
     */
    public function value();

    /**
     * Set the mutation value.
     *
     * @param mixed $value
     * @return $this
     */
    public function setValue($value);

    /**
     * Get the mutation extras.
     *
     * @return array
     */
    public function extras(): array;

    /**
     * Set the mutation ectras.
     *
     * @param array $extras
     * @return $this
     */
    public function setExtras(array $extras);

    /**
     * Check if the mutation has ectras.
     *
     * @param array $extras
     * @return bool
     */
    public function hasExtras(): bool;

    /**
     * Stop mutation propagation.
     *
     * @return $this
     */
    public function stop();

    /**
     * Reset mutation propagation.
     *
     * @return $this
     */
    public function resume();

    /**
     * Check whether propagation was stopped.
     *
     * @return bool
     */
    public function stopped();

    /**
     * After the value is mutated
     *
     * @param callable $callback
     * @return $this
     */
    public function setAfter(callable $callback): self;

    /**
     * Before the value is mutated
     *
     * @param callable $callback
     * @return $this
     */
    public function setBefore(callable $callback): self;

    /**
     * Check if after callback exists
     *
     * @return bool
     */
    public function hasAfter(): bool;

    /**
     * Check if before callback exists
     *
     * @return bool
     */
    public function hasBefore(): bool;

    /**
     * Invoke after callback
     *
     * @return $this
     */
    public function invokeAfter(): self;

    /**
     * Invoke before callback
     *
     * @return $this
     */
    public function invokeBefore(): self;

    /**
     * Get the mutation name.
     *
     * @return string
     */
    public function getName();
}
