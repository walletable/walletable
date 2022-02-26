<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Walletable\Actions\Action;
use Walletable\Contracts\WalletInterface;
use Walletable\Facades\Wallet as FacadesWallet;
use Walletable\Models\Traits\WalletRelations;
use Walletable\Models\Traits\WorkWithData;
use Walletable\Money\Money;
use Walletable\Wallet\Transaction\CreditDebit;
use Walletable\Wallet\Transaction\Transfer;
use Walletable\Traits\ConditionalUuid;
use Walletable\WalletManager;

/**
 * @property-read \Walletable\Money\Money $amount
 * @property-read \Walletable\Money\Currency $currency
 */
class Wallet extends Model implements WalletInterface
{
    use HasFactory;
    use ConditionalUuid;
    use WalletRelations;
    use WorkWithData;

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
    public function getAmountAttribute()
    {
        return new Money(
            $this->getRawOriginal('amount'),
            $this->currency
        );
    }

    public function getDriverAttribute()
    {
        return App::make(WalletManager::class)->driver($this->getRawOriginal('driver'));
    }

    /**
     * Get the currency object of the wallet
     *
     * @return \Walletable\Money\Currency
     */
    public function getCurrencyAttribute()
    {
        return $this->driver->currency($this->getRawOriginal('currency'));
    }

    /**
     * Check if this wallet is compactible with another wallet
     *
     * @param self $wallet
     */
    public function compactible(self $wallet): bool
    {
        return FacadesWallet::compactible($this, $wallet);
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
     * @return \Walletable\Actions\Action
     */
    public function action(string $action): Action
    {
        if (isset($this->instanceCache['action'])) {
            return $this->instanceCache['action'];
        }

        return $this->instanceCache['action'] = new Action(
            $this,
            App::make(WalletManager::class)
                ->action($action)
        );
    }
}
