<?php

namespace Walletable\Apis\Wallet;

use Walletable\Contracts\Walletable;
use Walletable\Models\Wallet;

class NewWallet
{
    /**
     * Verification Data
     *
     * @var array
     */
    protected $data = [];

    protected function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Create and populate new payment object with data
     *
     */
    public static function new(
        string $reference,
        string $name,
        string $email,
        string $label,
        string $tag,
        string $currency,
        Wallet $model,
        Walletable $walletable
    ): self {
        return new static([
            'reference' => $reference,
            'name' => $name,
            'email' => $email,
            'label' => $label,
            'tag' => $tag,
            'currency' => $currency,
            'model' => $model,
            'walletable' => $walletable,
        ]);
    }

    /**
     * Dinamically map undefined properties to data property
     */
    public function __get(string $method)
    {
        if (isset($this->data[$method])) {
            return $this->data[$method];
        }
    }
}
