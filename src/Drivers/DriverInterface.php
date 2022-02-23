<?php

namespace Walletable\Drivers;

use Illuminate\Support\Collection;
use Walletable\Apis\Balance\Alteration;
use Walletable\Apis\Wallet\NewWallet;
use Walletable\Contracts\Walletable;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Currencies;

interface DriverInterface
{
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
        string $reference,
        string $name,
        string $email,
        string $label,
        string $tag,
        string $currency,
        Wallet $model,
        Walletable $walletable
    ): NewWallet;

    /**
     * Make crediting calculations
     *
     * @param int $balance
     * @param int $amount
     * @param \Walletable\Models\Transaction $transaction
     * @param \Walletable\Models\Wallet $model
     * @param \Walletable\Contracts\Walletable $walletable
     * @param bool $calculateOnly = false
     *
     * @return bool
     */
    public function credit(
        int $balance,
        int $amount,
        Transaction $transaction,
        Wallet $model,
        Walletable $walletable,
        bool $calculateOnly = false
    ): Alteration;

    /**
     * Make debiting calculations
     *
     * @param int $balance
     * @param int $amount
     * @param \Walletable\Models\Transaction $transaction
     * @param \Walletable\Models\Wallet $model
     * @param \Walletable\Contracts\Walletable $walletable
     * @param bool $calculateOnly = false
     *
     * @return bool
     */
    public function debit(
        int $balance,
        int $amount,
        Transaction $transaction,
        Wallet $model,
        Walletable $walletable,
        bool $calculateOnly = false
    ): Alteration;

    /**
     *  This method is used to check if a wallet can be debited with a particular amount
     *
     * @param int $balance
     * @param int $amount
     * @param \Walletable\Models\Wallet $model
     * @param \Walletable\Contracts\Walletable $walletable
     *
     * @return bool
     */
    public function debitable(int $balance, int $amount, Wallet $model, Walletable $walletable): bool;


    /**
     *  This method is used to check if a wallet can be credited with a particular amount
     *
     * @param int $balance
     * @param int $amount
     * @param \Walletable\Models\Wallet $model
     * @param \Walletable\Contracts\Walletable $walletable
     *
     * @return bool
     */
    public function creditable(int $balance, int $amount, Wallet $model, Walletable $walletable): bool;

    /**
     * Get collection of supported currencies
     */
    public function currencies(): Currencies;
}
