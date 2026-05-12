<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Walletable\Contracts\Walletable as WalletableContract;
use Walletable\Traits\ConditionalID;

/**
 * The single counter-account per currency. Every external credit/debit
 * posts against a house wallet; transfers do not. House balances are
 * mirrors of user balances and are negative by design.
 *
 * @property int $id
 * @property string $currency
 */
class HouseAccount extends Model implements WalletableContract
{
    use ConditionalID;

    public $timestamps = false;

    protected $fillable = ['currency'];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function wallets(): MorphMany
    {
        return $this->morphMany(config('walletable.models.wallet'), 'walletable');
    }

    /**
     * Convenience: the single wallet that mirrors this house account's currency.
     */
    public function wallet(): ?Wallet
    {
        return $this->wallets()->first();
    }

    /**
     * Resolve (lazily creating if missing) the house wallet for a currency.
     * Used by single-leg executors so callers don't have to seed manually.
     */
    public static function walletFor(string $currency): Wallet
    {
        $house = static::query()->firstOrCreate(
            ['currency' => $currency],
            ['created_at' => now()]
        );

        return $house->ensureWallet();
    }

    /**
     * Ensure a mirrored wallet row exists; return it.
     */
    public function ensureWallet(): Wallet
    {
        if ($wallet = $this->wallets()->first()) {
            return $wallet;
        }

        $walletClass = config('walletable.models.wallet');

        /** @var Wallet $wallet */
        $wallet = new $walletClass();
        $wallet->walletable_id = $this->getKey();
        $wallet->walletable_type = $this->getMorphClass();
        $wallet->forceFill([
            'label' => 'House — ' . $this->currency,
            'tag' => 'house',
            'currency' => $this->currency,
            'amount' => 0,
            'meta' => '{}',
        ])->save();

        return $wallet;
    }

    public function getOwnerName()
    {
        return 'House — ' . $this->currency;
    }

    public function getOwnerEmail()
    {
        return null;
    }

    public function getOwnerID()
    {
        return $this->getKey();
    }

    public function getOwnerMorphName()
    {
        return $this->getMorphClass();
    }
}
