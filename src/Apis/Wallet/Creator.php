<?php

namespace Walletable\Apis\Wallet;

use Exception;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Walletable\Drivers\DriverInterface;
use Walletable\Events\CreatingWallet;
use Walletable\Events\WalletCreated;
use Walletable\Facades\Wallet;
use Walletable\Models\Wallet as WalletModel;
use Walletable\WalletManager;

class Creator
{
    /**
     * Data to create a new wallet
     *
     * @var array
     */
    protected $data = [];

    /**
     * Accepted data key
     *
     * @var array
     */
    protected $accepted_key = [
        'reference',
        'name',
        'email',
        'label',
        'walletable',
        'tag',
        'currency',
        'driver'
    ];

    /**
     * Data to create a new wallet
     *
     * @var \Walletable\Drivers\DriverInterface
     */
    protected $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
        $this->data['driver'] = App::make(WalletManager::class)->driverName(get_class($driver));
    }

    /**
     * Dynamically foward property assigning to data property
     *
     * @param string $name
     * @param mixed $value
     */
    public function __call(string $method, $parameters)
    {
        if (in_array($method, $this->accepted_key) && count($parameters)) {
            $this->data[$method] = $parameters[0];
        }

        return $this;
    }

    /**
     * Check if all entries are filled
     *
     * @param bool $throw
     * @return bool
     */
    protected function filled(bool $throw = false)
    {
        $empty = [];
        foreach ($this->accepted_key as $value) {
            if (!isset($this->data[$value]) || is_null($this->data[$value])) {
                $empty[] = $value;
            }
        }

        if (count($empty) && $throw) {
            throw new Exception("Missing value(s): " . implode(',', $empty));
        } else {
            return (count($empty)) ? false : true;
        }
    }

    /**
     * Create a new wallet
     *
     * @return \Walletable\Apis\Wallet\NewWallet
     */
    public function create(): NewWallet
    {
        if (!Wallet::supportedCurrency($this->driver, $this->data['currency'])) {
            throw new InvalidArgumentException("[{$this->data['currency']}] is not a supported currency");
        }

        $event = App::make('events');
        $walletable = $this->data['walletable'];

        $model = $this->newWalletModel();
        $model->driver = $this->data['driver'];
        $model->walletable_id = $walletable->getOwnerID();
        $model->walletable_type = $walletable->getOwnerMorphName();

        $newWallet = $this->driver->create(
            $this->data['reference'],
            $this->data['name'],
            $this->data['email'],
            $this->data['label'],
            $this->data['tag'],
            $this->data['currency'],
            $model,
            $walletable,
        );

        $model->forceFill([
            'label' => $newWallet->label,
            'tag' => $newWallet->tag,
            'amount' => $newWallet->amount,
            'currency' => $newWallet->currency,
            'data' => $newWallet->data,
            'amount' => 0
        ]);

        $event->dispatch(new CreatingWallet(
            $this->driver,
            $model,
            $walletable
        ));

        $model->save();

        $event->dispatch(new WalletCreated(
            $this->driver,
            $model,
            $walletable
        ));

        return $newWallet;
    }

    /**
     * Get a fresh wallet model
     *
     * @return \Walletable\Models\Wallet
     */
    public function newWalletModel(): WalletModel
    {
        return App::make(WalletModel::class);
    }
}
