<?php

namespace Walletable;

use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Internals\Actions\Traits\HasActions;
use Walletable\Internals\Creator;
use Walletable\Contracts\Walletable;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Lockers\Traits\HasLockers;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Currencies;
use Walletable\Money\Currency;
use Walletable\Transaction\TransactionBag;

class WalletManager
{
    use ForwardsCalls;
    use Macroable;
    use HasLockers;
    use HasActions;

    /**
     * Collection of supported currencies
     *
     * @var \Walletable\Money\Currencies
     */
    protected $currencies;

    public function __construct()
    {
        //
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
     * @return \Walletable\Models\Wallet
     */
    public function create(
        Walletable $walletable,
        string $reference,
        string $label,
        string $tag,
        string $currency
    ): Wallet {
        $creator = new Creator($walletable);

        return $creator->reference($reference)
            ->name($walletable->getOwnerName())
            ->email($walletable->getOwnerEmail())
            ->label($label)
            ->tag($tag)
            ->currency($currency)->create();
    }

    /**
     * Check if currency is supported
     *
     * @param string $currency
     *
     * @return bool
     */
    public function supportedCurrency(string $currency)
    {
        return !is_null($this->currencies->get($currency));
    }

    /**
     * Get currency instance
     *
     * @param string $currency
     *
     * @return bool
     */
    public function currency(string $currency)
    {
        if (is_null($currency = $this->currencies->get($currency))) {
            throw new InvalidArgumentException(sprintf('[%s] currency not supported'));
        }

        return $currency;
    }

    /**
     * Check if currency is supported
     *
     * @param string $
     *
     * @return bool
     */
    public function supportedCurrencies(Currency ...$currencies)
    {
        $this->currencies = Currencies::create(...$currencies);

        return $this;
    }

    /**
     * Check if the two wallets are compactable
     *
     * @param \Walletable\Models\Wallet $wallet
     * @param \Walletable\Models\Wallet $against
     *
     * @return bool
     */
    public function compactible(Wallet $wallet, Wallet $against)
    {
        return $wallet->currency->getCode() === $against->currency->getCode();
    }

    /**
     * Apply action to a transaction model
     *
     * @param \Walletable\Internals\Actions\ActionInterface|string $action
     * @param \Walletable\Transaction\TransactionBag|\Walletable\Models\Transaction $transactions Transactions
     * @param \Walletable\Internals\Actions\ActionData $data
     */
    public function applyAction($action, object $transactions, ActionData $data)
    {
        if (!($transactions instanceof TransactionBag) && !($transactions instanceof Transaction)) {
            throw new InvalidArgumentException(
                'Argument 2 can be either an instance of ' .
                    TransactionBag::class . ' or ' . Transaction::class
            );
        }

        if (!is_string($action) && !($action instanceof ActionInterface)) {
            throw new InvalidArgumentException('Argument 1 must be of type ' . ActionInterface::class . ' or String');
        }

        if (is_string($action)) {
            $action = $this->action($action);
        }

        if ($transactions instanceof TransactionBag) {
            $transactions->each(function ($transaction) use ($data, $action) {
                $action->apply($transaction, $data);
            });
            return;
        }

        $action->apply($transactions, $data);
    }
}
