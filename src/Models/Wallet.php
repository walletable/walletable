<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use InvalidArgumentException;
use Walletable\Contracts\WalletInterface;
use Walletable\Facades\Wallet as FacadesWallet;
use Walletable\Models\Traits\WalletRelations;
use Walletable\Models\Traits\WorkWithData;
use Walletable\Money\Money;
use Walletable\Services\Transaction\Transfer;
use Walletable\Traits\ConditionalUuid;
use Walletable\WalletManager;

class Wallet extends Model implements WalletInterface
{
    use HasFactory;
    use ConditionalUuid;
    use WalletRelations;
    use WorkWithData;

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
        if (!is_int($amount) || $amount instanceof Money) {
            throw new InvalidArgumentException('\$amount type must be Money object or Integer');
        }

        if (is_int($amount)) {
            $amount = new Money($amount, $this->currency);
        }

        return (new Transfer($this, $amount, $wallet, $remarks))->execute();
    }
}
