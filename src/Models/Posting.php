<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;
use Walletable\Internals\Actions\ActionManager;
use Walletable\Ledger\LedgerImmutableException;
use Walletable\Models\Traits\WorkWithMeta;
use Walletable\Money\Currency;
use Walletable\Money\Money;
use Walletable\Traits\ConditionalID;
use Walletable\WalletableManager;

/**
 * A single immutable leg of a journal entry. Each posting is one direction
 * (D or C) against one wallet; per entry, debits and credits sum to zero.
 *
 * Postings can never be updated or deleted after insert.
 *
 * @property int $id
 * @property int $transaction_id
 * @property int $wallet_id
 * @property string $direction         D | C
 * @property int $balance_after        signed bigint
 * @property string $action            per-action reporting tag
 * @property string|null $method_id
 * @property string|null $method_type
 * @property-read \Walletable\Money\Money $amount
 * @property-read \Walletable\Money\Money $balance
 * @property-read \Walletable\Money\Currency $currency
 * @property-read \Walletable\Internals\Actions\ActionManager $action
 * @property-read string|null $title
 * @property-read string|null $image
 */
class Posting extends Model
{
    use ConditionalID;
    use WorkWithMeta;
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    public const DIRECTION_DEBIT = 'D';
    public const DIRECTION_CREDIT = 'C';

    public $timestamps = false;

    protected $fillable = [
        'transaction_id',
        'wallet_id',
        'direction',
        'amount',
        'currency',
        'balance_after',
        'action',
        'method_id',
        'method_type',
        'meta',
        'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
        'meta' => 'array',
    ];

    protected $postingCache = [];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(config('walletable.models.wallet'));
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(config('walletable.models.transaction'));
    }

    public function method(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountAttribute(): Money
    {
        return new Money($this->getRawOriginal('amount'), $this->currency);
    }

    public function getBalanceAttribute(): Money
    {
        return new Money($this->getRawOriginal('balance_after'), $this->currency);
    }

    public function getCurrencyAttribute(): Currency
    {
        return Money::currency($this->getRawOriginal('currency'));
    }

    public function getActionAttribute(): ActionManager
    {
        if (isset($this->postingCache['action'])) {
            return $this->postingCache['action'];
        }

        return $this->postingCache['action'] = new ActionManager(
            $this,
            App::make(WalletableManager::class)->action($this->getRawOriginal('action'))
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

    public function getMethodResource()
    {
        return $this->action->resource();
    }

    public function isCredit(): bool
    {
        return $this->getRawOriginal('direction') === self::DIRECTION_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->getRawOriginal('direction') === self::DIRECTION_DEBIT;
    }

    /**
     * Compatibility shim: callers familiar with the old Transaction API
     * sometimes inspect `$tx->type`. Return 'credit'/'debit' derived from direction.
     */
    public function getTypeAttribute(): string
    {
        return $this->isCredit() ? 'credit' : 'debit';
    }

    public function save(array $options = [])
    {
        if ($this->exists) {
            throw new LedgerImmutableException('Postings are append-only and cannot be updated.');
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new LedgerImmutableException('Postings are append-only and cannot be deleted.');
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return parent::__call($method, $parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return static::macroCallStatic($method, $parameters);
        }

        return parent::__callStatic($method, $parameters);
    }
}
