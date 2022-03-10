<?php

namespace Walletable\Internals;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;
use Walletable\Contracts\Walletable;
use Walletable\Events\CreatingWallet;
use Walletable\Events\CreatedWallet;
use Walletable\Facades\Wallet;
use Walletable\Models\Wallet as WalletModel;

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
        'tag',
        'currency'
    ];

    /**
     * Data to create a new wallet
     *
     * @var \Walletable\Contracts\Walletable
     */
    protected $walletable;

    public function __construct(Walletable $walletable)
    {
        $this->walletable = $walletable;
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
            throw new Exception(sprintf('Missing value(s): %s', implode(',', $empty)));
        } else {
            return (count($empty)) ? false : true;
        }
    }

    /**
     * Create a new wallet
     *
     * @return \Walletable\Models\Wallet
     */
    public function create(): WalletModel
    {
        if (!Wallet::supportedCurrency($this->data['currency'])) {
            throw new InvalidArgumentException(sprintf('[%s] is not a supported currency.', $this->data['currency']));
        }

        $event = App::make('events');
        $walletable = $this->walletable;

        $model = $this->newWalletModel();
        $model->walletable_id = $walletable->getOwnerID();
        $model->walletable_type = $walletable->getOwnerMorphName();

        $model->forceFill([
            'label' => $this->data['label'],
            'tag' => $this->data['tag'],
            'currency' => $this->data['currency'],
            'data' => '{}',
            'amount' => 0
        ]);

        $event->dispatch(new CreatingWallet(
            $model,
            $walletable
        ));

        $model->save();

        $event->dispatch(new CreatedWallet(
            $model,
            $walletable
        ));

        return $model;
    }

    /**
     * Get a fresh wallet model
     *
     * @return \Walletable\Models\Wallet
     */
    public function newWalletModel(): WalletModel
    {
        return App::make(Config::get('walletable.models.wallet'));
    }
}
