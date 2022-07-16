<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Models\Traits\TransactionRelations;
use Walletable\Models\Traits\WorkWithMeta;
use Walletable\Money\Currency;
use Walletable\Money\Money;
use Walletable\Traits\ConditionalUuid;
use Walletable\WalletManager;

/**
 * @property-read \Walletable\Money\Money $balance
 * @property-read \Walletable\Money\Money $amount
 * @property-read \Walletable\Money\Currency $currency
 * @property-read \Walletable\Internals\Actions\ActionManager $action
 * @property-read string $title
 * @property-read string $image
 */
class Transaction extends Model
{
    use ConditionalUuid;
    use TransactionRelations;
    use WorkWithMeta;

    public $timestamps = false;

    protected $transactionCache = [];

    public function getAmountAttribute(): Money
    {
        return new Money(
            $this->getRawOriginal('amount'),
            $this->currency
        );
    }

    public function getBalanceAttribute(): Money
    {
        return new Money(
            $this->getRawOriginal('balance'),
            $this->currency
        );
    }

    public function getActionAttribute(): ActionManager
    {
        if (isset($this->transactionCache['action'])) {
            return $this->transactionCache['action'];
        }

        return $this->transactionCache['action'] = new ActionManager(
            $this,
            App::make(WalletManager::class)
                ->action($this->getRawOriginal('action'))
        );
    }

    public function getTitleAttribute(): ?string
    {
        return $this->action->title();
    }

    public function getImageAttribute(): ?string
    {
        return $this->action->image();
    }

    public function getDetailsAttribute()
    {
        return $this->action->details();
    }

    public function getCurrencyAttribute(): Currency
    {
        return Money::currency($this->getRawOriginal('currency'));
    }
}
