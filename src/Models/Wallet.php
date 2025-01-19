<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Walletable\Internals\Actions\Action;
use Walletable\Contracts\WalletInterface;
use Walletable\Facades\Mutator;
use Walletable\Facades\Walletable;
use Walletable\Internals\Mutation\System\WalletBalanceMutation;
use Walletable\Models\Traits\WalletRelations;
use Walletable\Models\Traits\WorkWithMeta;
use Walletable\Money\Money;
use Walletable\Transaction\CreditDebit;
use Walletable\Transaction\Transfer;
use Walletable\Traits\ConditionalID;
use Walletable\WalletableManager;

/**
 * @property-read \Walletable\Money\Money $balance
 * @property \Walletable\Money\Money $amount
 * @property-read \Walletable\Money\Currency $currency
 */
class Wallet extends Model implements WalletInterface
{
    use ConditionalID;
    use WalletRelations;
    use WorkWithMeta;
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    /**
     * Hold object for the wallet
     * @var array
     */
    protected $instanceCache = [];

    /**
     * Get the real balance object of a wallet
     *
     * @return \Walletable\Money\Money
     */
    public function getAmountAttribute($value)
    {
        return new Money(
            $value,
            $this->currency
        );
    }

    /**
     * Get the real balance object of a wallet
     *
     * @return \Walletable\Money\Money
     */
    public function getBalanceAttribute()
    {
        return Mutator::mutate(new WalletBalanceMutation(
            'wallet.balance',
            new Money(
                $this->getRawOriginal('amount'),
                $this->currency
            ),
            [
                'wallet' => $this
            ]
        ))->value();
    }

    /**
     * Get the currency object of the wallet
     *
     * @return \Walletable\Money\Currency
     */
    public function getCurrencyAttribute()
    {
        return Money::currency($this->getRawOriginal('currency'));
    }

    /**
     * Check if this wallet is compactible with another wallet
     *
     * @param self $wallet
     */
    public function compactible(self $wallet): bool
    {
        return Walletable::compactible($this, $wallet);
    }

    /**
     * Transfer money to another wallet
     *
     * @param self $wallet
     * @param int|\Walletable\Money\Money $amount
     * @param string|null $remarks
     */
    public function transfer(self $wallet, $amount, string $remarks = null): Transfer
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException('Argument 2 must be of type ' . Money::class . ' or Integer');
        }

        if (is_int($amount)) {
            $amount = $this->money($amount);
        }

        return (new Transfer($this, $amount, $wallet, $remarks))->execute();
    }

    /**
     * Credit the wallet
     *
     * @param int|\Walletable\Money\Money $amount
     * @param string|null $title
     * @param string|null $remarks
     */
    public function credit($amount, string $title = null, string $remarks = null): CreditDebit
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException('Argument 1 must be of type ' . Money::class . ' or Integer');
        }

        if (is_int($amount)) {
            $amount = $this->money($amount);
        }

        return (new CreditDebit('credit', $this, $amount, $title, $remarks))->execute();
    }

    /**
     * Debit the wallet
     *
     * @param int|\Walletable\Money\Money $amount
     * @param string|null $title
     * @param string|null $remarks
     */
    public function debit($amount, string $title = null, string $remarks = null): CreditDebit
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException('Argument 1 must be of type ' . Money::class . ' or Integer');
        }

        if (is_int($amount)) {
            $amount = $this->money($amount);
        }

        return (new CreditDebit('debit', $this, $amount, $title, $remarks))->execute();
    }

    /**
     * Return money object of thesame currency
     *
     * @param int $amount
     *
     * @return \Walletable\Money\Money
     */
    public function money(int $amount)
    {
        return new Money(
            $amount,
            $this->currency
        );
    }

    /**
     * Create action for the wallet
     *
     * @param string $action the name of the action
     * @return \Walletable\Internals\Actions\Action
     */
    public function action(string $action): Action
    {
        if (isset($this->instanceCache['actions'][$action])) {
            return $this->instanceCache['actions'][$action];
        }

        return $this->instanceCache['actions'][$action] = new Action(
            $this,
            App::make(WalletableManager::class)
                ->action($action)
        );
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the parrent.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return parent::__call($method, $parameters);
    }

    /**
     * Handle dynamic static calls into macros or pass missing methods to the parrent.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return static::macroCallStatic($method, $parameters);
        }

        return parent::__callStatic($method, $parameters);
    }
}
