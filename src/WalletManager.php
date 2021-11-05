<?php

namespace Walletable;

use Closure;
use Exception;
use Illuminate\Support\Traits\ForwardsCalls;
use InvalidArgumentException;
use Walletable\Apis\Wallet\Creator;
use Walletable\Apis\Wallet\NewWallet;
use Walletable\Contracts\Walletable;
use Walletable\Drivers\DriverInterface;
use Walletable\Models\Wallet;

class WalletManager
{
    use ForwardsCalls;

    /**
     * Unresolved driver arrays
     *
     * @var array
     */
    protected $resolvers = [];

    /**
     * Resoved drivers array
     *
     * @var array
     */
    protected $drivers = [];

    /**
     * Driver class map
     *
     * @var array
     */
    protected $classMap = [];

    public function __construct()
    {
        //
    }

    /**
     * Get Default driver
     *
     * @return \App\Services\Card\Contracts\CardDriverInterface
     */
    public function default(): DriverInterface
    {
        return $this->driver(config('walletable.default'));
    }

    /**
     * Get Default driver name
     *
     * @return string
     */
    public function defaultDriverName(): string
    {
        return config('walletable.default');
    }

    /**
     * Create a new wallet
     *
     * @param string $reference
     * @param string $name
     * @param string $email
     * @param string $label
     * @param string $tag
     * @param string $currency
     * @param \Walletable\Models\Wallet $model
     * @param \Walletable\Contracts\Walletable $walletable
     *
     * @return \Walletable\Apis\Wallet\NewWallet
     */
    public function create(
        Walletable $walletable,
        string $reference,
        string $label,
        string $tag,
        string $currency
    ): NewWallet {

        $creator = new Creator($this->default());
        return $creator->reference($reference)
            ->name($walletable->getOwnerName())
            ->email($walletable->getOwnerEmail())
            ->label($label)
            ->tag($tag)
            ->currency($currency)
            ->walletable($walletable)->create();
    }

    /**
     * Create a new wallet
     *
     * @param string $driver
     * @param \Walletable\Contracts\Walletable $walletable
     * @param string $reference
     * @param string $name
     * @param string $email
     * @param string $label
     * @param string $tag
     * @param string $currency
     *
     * @return \Walletable\Apis\Wallet\NewWallet
     */
    public function createWith(
        string $driverName,
        Walletable $walletable,
        string $reference,
        string $label,
        string $tag,
        string $currency
    ): NewWallet {
        if (($driver = $this->driver($driverName)) instanceof DriverInterface) {
            throw new InvalidArgumentException("[$driverName] is not a driver");
        }

        $creator = new Creator($driver);
        return $creator->reference($reference)
            ->name($walletable->getOwnerName())
            ->email($walletable->getOwnerEmail())
            ->label($label)
            ->label($label)
            ->tag($tag)
            ->currency($currency)
            ->walletable($walletable)->create();
    }

    /**
     * Check if currency is support by the driver
     *
     * @param \Walletable\Drivers\DriverInterface
     * @param string $currency
     *
     * @return bool
     */
    public function supportedCurrency(DriverInterface $driver, $currency)
    {
        return isset($driver->currencies()[$currency]);
    }

/*
    public function generateForModel(
        string $label,
        string $tag,
        string $currency, Contracts\DriverInterface $driver, Contracts\Walletable $walletable)
    {
        $owner_id = $owner->{$walletable->getKeyName()};
        $owner_type = get_class($walletable);
        $wallet = app(config('walletable.models.wallet'))->fill(
            [
                'walletable_id' => $owner_id,
                'walletable_type' => $owner_type,
                'label' => $label,
                'name' => $name,
                'driver' => $driver->signature(),
                'balance' => 0,
                'data' => '{}',
            ]
        );

        $i = 1;
        while ($i <= config('wallet.generation.tries', 5)) {

            $result = $driverClass::generate( $wallet, $walletable);

            if ($result['success']) {
                break;
            }

            $i++;

        }

        if ($result['success']) {
            $wallet->fill(
                $result['data']
            )->save();
            return $this->make($wallet);
        }else{
            return false;
        }
    }
 */
    /**
     * Forward methods calls
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->default(), $method, $parameters);
    }

    /**
     * Load driver to the unresolved array
     *
     * @param string $name
     * @param string|\Closure|null $driver
     *
     * @return \Walletable\Drivers\DriverInterface|void
     */
    public function driver(string $name, $driver = null)
    {
        if (
            !is_null($driver) &&
            !is_string($driver) &&
            !($driver instanceof \Closure)
        ) {
            throw new InvalidArgumentException('A driver can only be resolved through class name or closure');
        }

        if (!is_null($driver)) {
            if (
                is_string($driver) &&
                !(class_exists($driver) && is_subclass_of($driver, DriverInterface::class))
            ) {
                throw new Exception('Driver class must implement ' . DriverInterface::class);
            }

            $this->resolvers[$name] = $driver;
        } else {
            return $this->getResolvedDriver($name);
        }
    }

    /**
     * Resolve or get an already resolved driver instance
     *
     * @param string $name
     */
    protected function getResolvedDriver(string $name)
    {
        if (!isset($this->resolvers[$name])) {
            throw new Exception("\"$name\" not found as an wallet driver");
        }

        if (!isset($this->drivers[$name])) {
            if (($resolver = $this->resolvers[$name]) instanceof \Closure) {
                $driver = $this->resolveDriverFromClosure($resolver);
            } else {
                $driver = $this->resolveDriverFromClass($resolver);
            }

            $this->classMap[get_class($driver)] = $name;
            return $this->drivers[$name] = $driver;
        } else {
            return $this->drivers[$name];
        }
    }

    /**
     * Resolve a driver from closure
     *
     * @param Closure $resolver
     *
     * @return \Walletable\Drivers\DriverInterface
     */
    protected function resolveDriverFromClosure(Closure $resolver): DriverInterface
    {
        if (!($driver = app()->call($resolver)) instanceof DriverInterface) {
            throw new Exception('Closure resolver must return an instance of ' . DriverInterface::class);
        }

        return $driver;
    }

    /**
     * Resolve a driver from string
     *
     * @param string $resolver
     *
     * @return \Walletable\Drivers\DriverInterface
     */
    protected function resolveDriverFromClass(string $resolver): DriverInterface
    {
        return app()->make($resolver);
    }

    /**
     * Get the driver name in from the class map
     *
     * @param string $class
     * @return array
     */
    public function driverName(string $class)
    {
        return $this->classMap[$class] ?? null;
    }
}
