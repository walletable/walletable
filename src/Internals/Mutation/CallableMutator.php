<?php

namespace Walletable\Internals\Mutation;

class CallableMutator implements MutatorInterface
{
    /**
     * The callback.
     *
     * @var callable
     */
    protected $callback;

    /**
     * Create a new callback listener instance.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * Get the callback.
     *
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    /**
     * @inheritdoc
     */
    public function mutate(MutationInterface $mutation)
    {
        if ($mutation->hasExtras() && !($this->callback instanceof self)) {
            app()->call($this->callback, array_merge([
                'mutation' => $mutation
            ], $mutation->extras()));
            return;
        }

        call_user_func_array($this->callback, func_get_args());
    }

    /**
     * @inheritdoc
     */
    public function isMutator($listener)
    {
        if ($listener instanceof CallableMutator) {
            $listener = $listener->getCallback();
        }

        return $this->callback === $listener;
    }

    /**
     * Named constructor
     *
     * @param callable $callable
     *
     * @return static
     */
    public static function fromCallable(callable $callable)
    {
        return new static($callable);
    }
}
