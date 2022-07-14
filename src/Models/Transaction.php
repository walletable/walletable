<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Models\Traits\TransactionRelations;
use Walletable\Models\Traits\WorkWithMeta;
use Walletable\Money\Money;
use Walletable\Traits\ConditionalUuid;
use Walletable\WalletManager;

class Transaction extends Model
{
    use ConditionalUuid;
    use TransactionRelations;
    use WorkWithMeta;

    public $timestamps = false;

    protected $transactionCache = [];

    public function getAmountAttribute()
    {
        return new Money(
            $this->getRawOriginal('amount'),
            $this->currency
        );
    }

    public function getBalanceAttribute()
    {
        return new Money(
            $this->getRawOriginal('balance'),
            $this->currency
        );
    }

    public function getActionAttribute()
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

    public function getTitleAttribute()
    {
        return $this->action->title();
    }

    public function getImageAttribute()
    {
        return $this->action->image();
    }

    public function getDetailsAttribute()
    {
        return $this->action->details();
    }

    public function getCurrencyAttribute()
    {
        return Money::currency($this->getRawOriginal('currency'));
    }
}
