<?php

namespace Walletable\Internals\Mutation;

use InvalidArgumentException;

class MutatorManager
{
    /**
     * High priority.
     *
     * @var int
     */
    public const PRIORITY_HIGH = 100;

    /**
     * Normal priority.
     *
     * @var int
     */
    public const PRIORITY_NORMAL = 0;

    /**
     * Low priority.
     *
     * @var int
     */
    public const PRIORITY_LOW = -100;

    /**
     * The registered mutators.
     *
     * @var array
     */
    protected $mutators = [];

    /**
     * The sorted mutators
     *
     * Mutators will get sorted and stored for re-use.
     *
     * @var mixed
     */
    protected $sortedMutators = [];


    /**
     * Undocumented function
     *
     * @param string $mutation
     * @param mixed $mutators
     * @param int $priority
     * @return $this
     */
    public function mutator(string $mutation, $mutators, $priority = self::PRIORITY_NORMAL)
    {
        $mutators = is_array($mutators) ? $mutators : [$mutators];

        foreach ($mutators as $mutator) {
            $mutator = $this->ensureMutator($mutator);
            $this->mutators[$mutation][$priority][] = $mutator;
        }

        $this->clearSortedMutators($mutation);

        return $this;
    }


    /**
     * @inheritdoc
     */
    public function getMutators(string $mutation): array
    {
        if (array_key_exists($mutation, $this->sortedMutators)) {
            return $this->sortedMutators[$mutation];
        }

        return $this->sortedMutators[$mutation] = $this->getSortedMutators($mutation);
    }

    /**
     * Ensure the input is a mutator.
     *
     * @param MutatorInterface|callable $mutator
     *
     * @throws InvalidArgumentException
     *
     * @return MutatorInterface
     */
    protected function ensureMutator($mutator)
    {
        if ($mutator instanceof MutatorInterface) {
            return $mutator;
        }

        if (is_callable($mutator)) {
            return CallableMutator::fromCallable($mutator);
        }

        throw new InvalidArgumentException(
            sprintf(
                'Mutators should be MutatorInterface, Closure or callable. Received type: %s',
                gettype($mutator)
            )
        );
    }

    /**
     * Get the mutators sorted by priority for a given mutation.
     *
     * @param string $mutation
     *
     * @return MutatorInterface[]
     */
    protected function getSortedMutators($mutation)
    {
        if (! $this->hasMutators($mutation)) {
            return [];
        }

        $mutators = $this->mutators[$mutation];
        krsort($mutators);

        return call_user_func_array('array_merge', $mutators);
    }


    /**
     * Execute a mutation
     *
     * @param MutationInterface $mutation
     * @return MutationInterface
     */
    public function mutate(MutationInterface $mutation)
    {
        $this->invokeMutators($mutation->getName(), $mutation);

        return $mutation;
    }

    /**
     * @inheritdoc
     */
    public function mutateBatch(array $mutations)
    {
        $results = [];

        foreach ($mutations as $key => $mutation) {
            $results[$key] = $this->mutate($mutation);
        }

        return $results;
    }

    /**
     * Invoke the mutators for an mutation.
     *
     * @param string         $name
     * @param MutationInterface $mutation
     * @param array          $arguments
     *
     * @return void
     */
    protected function invokeMutators($name, MutationInterface $mutation)
    {
        $mutators = $this->getMutators($name);

        if (method_exists($mutation, 'before')) {
            call_user_func([$mutation, 'before']);
        }

        if ($mutation->hasBefore()) {
            $mutation->invokeBefore();
        }

        foreach ($mutators as $mutator) {
            if ($mutation->stopped()) {
                break;
            }

            if ($mutation->hasExtras() && !($mutator instanceof CallableMutator)) {
                app()->call([$mutator, 'mutate'], array_merge([
                    'mutation' => $mutation
                ], $mutation->extras()));
                continue;
            }

            call_user_func_array([$mutator, 'mutate'], [$mutation]);
        }
        $mutation->resume();

        if ($mutation->hasAfter()) {
            $mutation->invokeAfter();
        }

        if (method_exists($mutation, 'after')) {
            call_user_func([$mutation, 'after']);
        }
    }

    /**
     * @inheritdoc
     */
    public function hasMutators($mutation)
    {
        if (! isset($this->mutators[$mutation]) || count($this->mutators[$mutation]) === 0) {
            return false;
        }

        return true;
    }

    /**
     * Clear the sorted mutators for an mutation
     *
     * @param $mutation
     */
    protected function clearSortedMutators($mutation)
    {
        unset($this->sortedMutators[$mutation]);
    }

    /**
     * Flush everything
     *
     * @return void
     */
    public function tearDown()
    {
        $this->mutators = [];
        $this->sortedMutators = [];
    }
}
