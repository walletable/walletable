<?php

namespace Walletable\Drivers;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Walletable\Apis\Balance\Alteration;
use Walletable\Apis\Wallet\NewWallet;
use Walletable\Contracts\Walletable;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Currencies;
use Walletable\Money\Currency;

class DatabaseDriver implements DriverInterface
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
    ): NewWallet {
        return NewWallet::new(
            $reference,
            $name,
            $email,
            $label,
            $tag,
            $currency,
            $model,
            $walletable
        );
    }

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
    ): Alteration {
        $newBalance = $balance + $amount;
        return Alteration::new(Str::uuid(), 'cedit', $newBalance, $amount);
    }

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
    ): Alteration {
        $newBalance = $balance - $amount;
        return Alteration::new(Str::uuid(), 'cedit', $newBalance, $amount);
    }

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
    public function debitable(int $balance, int $amount, Wallet $model, Walletable $walletable): bool
    {
        return $balance > $amount;
    }


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
    public function creditable(int $balance, int $amount, Wallet $model, Walletable $walletable): bool
    {
        return true;
    }

    /**
     *  Get the currency profiles of supported currencies
     *
     *  @return array
     */
    public function currencies(): Currencies
    {
        return Currencies::create(
            Currency::new(
                'NGN',
                '₦',
                'Naira',
                'Kobo',
                100,
                566
            ),
            Currency::new(
                'USD',
                '$',
                'Dollar',
                'Cent',
                100,
                840
            ),
            Currency::new(
                'GBP',
                '£',
                'Pound Sterling',
                'Pence',
                100,
                826
            ),
            Currency::new(
                'CAD',
                '¢',
                'Pound Sterling',
                'Pence',
                100,
                124
            ),
        );
    }

    /**
     *  Get the currency profiles of supported currencies
     *
     * @param string $code
     *  @return array
     */
    public function currency(string $code): Currency
    {
        $data = $this->currencies();

        if (!($data = $data->get($code))) {
            throw new InvalidArgumentException("Currency [{$code}] is not supported by this driver", 1);
        }

        return Currency::new(
            $code,
            $data['symbol'],
            $data['name'],
            $data['subunit'],
            $data['per'] ?? null,
            $data['numeric'] ?? null
        );
    }
}
