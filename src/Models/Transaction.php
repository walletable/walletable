<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Walletable\Ledger\LedgerImmutableException;
use Walletable\Models\Traits\WorkWithMeta;
use Walletable\Money\Currency;
use Walletable\Money\Money;
use Walletable\Traits\ConditionalID;

/**
 * A business event in the ledger: the append-only header for a balanced
 * set of postings. The transaction is the source of truth; wallet balances
 * are a materialised projection of the postings under it.
 *
 * Allowed mutations after initial insert:
 *   pending  -> posted  (sets posted_at; draft_postings is cleared)
 *   pending  -> voided  (draft_postings is cleared)
 * Any other field change throws LedgerImmutableException.
 *
 * @property int $id
 * @property string $currency
 * @property string $status            pending | posted | voided
 * @property \Illuminate\Support\Carbon|null $posted_at
 * @property string|null $narration
 * @property string|null $reference_type
 * @property string|null $reference_id
 * @property int|null $reverses_id
 * @property array|null $meta
 * @property array|null $draft_postings
 */
class Transaction extends Model
{
    use ConditionalID;
    use WorkWithMeta;

    public const STATUS_PENDING = 'pending';
    public const STATUS_POSTED = 'posted';
    public const STATUS_VOIDED = 'voided';

    /**
     * Columns owned by the package. Applications cannot declare these as
     * extra columns through Walletable::extendTransaction().
     */
    public const RESERVED_COLUMNS = [
        'id',
        'currency',
        'status',
        'posted_at',
        'narration',
        'reference_type',
        'reference_id',
        'reverses_id',
        'meta',
        'draft_postings',
        'created_at',
    ];

    public $timestamps = false;

    protected $fillable = [
        'currency',
        'status',
        'posted_at',
        'narration',
        'reference_type',
        'reference_id',
        'reverses_id',
        'meta',
        'draft_postings',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'draft_postings' => 'array',
        'posted_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * Columns that may legitimately change while transitioning out of pending.
     */
    protected const TRANSITION_FIELDS = ['status', 'posted_at', 'draft_postings'];

    protected function walletableKeys(): array
    {
        return ['reverses_id'];
    }

    public function postings(): HasMany
    {
        return $this->hasMany(config('walletable.models.posting'));
    }

    public function reversesTransaction(): BelongsTo
    {
        return $this->belongsTo(static::class, 'reverses_id');
    }

    public function isPending(): bool
    {
        return $this->getRawOriginal('status') === self::STATUS_PENDING;
    }

    public function isPosted(): bool
    {
        return $this->getRawOriginal('status') === self::STATUS_POSTED;
    }

    public function isVoided(): bool
    {
        return $this->getRawOriginal('status') === self::STATUS_VOIDED;
    }

    public function getCurrencyObjectAttribute(): Currency
    {
        return Money::currency($this->getRawOriginal('currency'));
    }

    /**
     * Enforce append-only semantics. Only the documented transitions are allowed.
     */
    public function save(array $options = [])
    {
        if ($this->exists) {
            $original = $this->getRawOriginal('status');
            $next = $this->getAttribute('status');

            $dirty = array_keys($this->getDirty());
            $disallowed = array_diff($dirty, self::TRANSITION_FIELDS);
            if (!empty($disallowed)) {
                throw new LedgerImmutableException(sprintf(
                    'Cannot modify immutable transaction fields: %s',
                    implode(', ', $disallowed)
                ));
            }

            if ($original !== $next) {
                if (
                    !($original === self::STATUS_PENDING && $next === self::STATUS_POSTED)
                    && !($original === self::STATUS_PENDING && $next === self::STATUS_VOIDED)
                ) {
                    throw new LedgerImmutableException(sprintf(
                        'Illegal transaction status transition: %s -> %s',
                        $original,
                        $next
                    ));
                }
            }
        }

        return parent::save($options);
    }

    public function delete()
    {
        throw new LedgerImmutableException('Transactions cannot be deleted.');
    }
}
