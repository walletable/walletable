<?php

namespace Walletable\Internals\Actions\Traits;

use Closure;
use Exception;
use InvalidArgumentException;
use Walletable\Internals\Actions\ActionInterface as ActionsActionInterface;
use Walletable\Internals\Actions\ActionInterface;

trait HasActions
{
    /**
     * Unresolved action arrays
     *
     * @var array
     */
    protected $actionResolvers = [];

    /**
     * Resolved action array
     *
     * @var array
     */
    protected $actions = [];

    /**
     * Load action to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $action
     *
     * @return \Walletable\Internals\Actions\ActionInterface|void
     */
    public function action(string $name, $action = null)
    {
        if (
            !is_null($action) &&
            !is_string($action) &&
            !($action instanceof \Closure)
        ) {
            throw new InvalidArgumentException('A action can only be resolved through class name or closure');
        }

        if (!is_null($action)) {
            if (
                is_string($action) &&
                !(class_exists($action) && is_subclass_of($action, ActionInterface::class))
            ) {
                throw new Exception(sprintf('Action class must implement %s', ActionsActionInterface::class));
            }

            $this->actionResolvers[$name] = $action;
        } else {
            return $this->getResolvedAction($name);
        }
    }

    /**
     * Resolve or get an already resolved action instance
     *
     * @param string $name
     */
    protected function getResolvedAction(string $name)
    {
        if (!isset($this->actionResolvers[$name])) {
            throw new Exception(sprintf('"%s" not found as a wallet action'), $name);
        }

        if (!isset($this->actions[$name])) {
            if (($resolver = $this->actionResolvers[$name]) instanceof \Closure) {
                $action = $this->resolveActionFromClosure($resolver);
            } else {
                $action = $this->resolveActionFromClass($resolver);
            }

            $this->classMap[get_class($action)] = $name;
            return $this->actions[$name] = $action;
        } else {
            return $this->actions[$name];
        }
    }

    /**
     * Resolve a action from closure
     *
     * @param Closure $resolver
     *
     * @return \Walletable\Internals\Actions\ActionInterface
     */
    protected function resolveActionFromClosure(Closure $resolver): ActionInterface
    {
        if (!($action = app()->call($resolver)) instanceof ActionInterface) {
            throw new Exception(sprintf('Closure resolver must return an instance of %s', ActionInterface::class));
        }

        return $action;
    }

    /**
     * Resolve a action from string
     *
     * @param string $resolver
     *
     * @return \Walletable\Internals\Actions\ActionInterface
     */
    protected function resolveActionFromClass(string $resolver): ActionInterface
    {
        return app()->make($resolver);
    }
}
