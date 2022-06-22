<?php

namespace Walletable\Internals\Lockers\Traits;

use Closure;
use Exception;
use InvalidArgumentException;
use Walletable\Internals\Lockers\LockerInterface;

trait HasLockers
{
    /**
     * Unresolved locker arrays
     *
     * @var array
     */
    protected $lockerResolvers = [];

    /**
     * Resolved locker array
     *
     * @var array
     */
    protected $lockers = [];

    /**
     * Load locker to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $locker
     *
     * @return \Walletable\Internals\Lockers\LockerInterface|void
     */
    public function locker(string $name, $locker = null)
    {
        if (
            !is_null($locker) &&
            !is_string($locker) &&
            !($locker instanceof \Closure)
        ) {
            throw new InvalidArgumentException('A locker can only be resolved through class name or closure');
        }

        if (!is_null($locker)) {
            if (
                is_string($locker) &&
                !(class_exists($locker) && is_subclass_of($locker, LockerInterface::class))
            ) {
                throw new Exception(sprintf(
                    'Locker class must implement [%s] interface',
                    LockerInterface::class
                ));
            }

            $this->lockerResolvers[$name] = $locker;
        } else {
            return $this->getResolvedLocker($name);
        }
    }

    /**
     * Resolve or get an already resolved locker instance
     *
     * @param string $name
     */
    protected function getResolvedLocker(string $name)
    {
        if (!isset($this->lockerResolvers[$name])) {
            throw new Exception("\"$name\" not found as a wallet locker");
        }

        if (!isset($this->lockers[$name])) {
            if (($resolver = $this->lockerResolvers[$name]) instanceof \Closure) {
                $locker = $this->resolveLockerFromClosure($resolver);
            } else {
                $locker = $this->resolveLockerFromClass($resolver);
            }

            $this->classMap[get_class($locker)] = $name;
            return $this->lockers[$name] = $locker;
        } else {
            return $this->lockers[$name];
        }
    }

    /**
     * Resolve a locker from closure
     *
     * @param Closure $resolver
     *
     * @return \Walletable\Internals\Lockers\LockerInterface
     */
    protected function resolveLockerFromClosure(Closure $resolver): LockerInterface
    {
        if (!($locker = app()->call($resolver)) instanceof LockerInterface) {
            throw new Exception('Closure resolver must return an instance of ' . LockerInterface::class);
        }

        return $locker;
    }

    /**
     * Resolve a locker from string
     *
     * @param string $resolver
     *
     * @return \Walletable\Internals\Lockers\LockerInterface
     */
    protected function resolveLockerFromClass(string $resolver): LockerInterface
    {
        return app()->make($resolver);
    }
}
