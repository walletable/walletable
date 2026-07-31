<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Traits\Macroable;
use InvalidArgumentException;
use Walletable\Contracts\WalletInterface;
use Walletable\Facades\Mutator;
use Walletable\Facades\Walletable;
use Walletable\Internals\Actions\Action;
use Walletable\Internals\Mutation\System\WalletBalanceMutation;
use Walletable\Models\Traits\WorkWithMeta;
use Walletable\Money\Money;
use Walletable\Traits\ConditionalID;
use Walletable\Transaction\Confirmation;
use Walletable\Transaction\CreditDebit;
use Walletable\Transaction\Transfer;
use Walletable\Transaction\UnconfirmedCreditDebit;
use Walletable\WalletableManager;

/**
 * @property-read \Walletable\Money\Money $balance
 * @property \Walletable\Money\Money $amount
 * @property-read \Walletable\Money\Currency $currency
 */
class Wallet extends Model implements WalletInterface
{
    use ConditionalID;
    use WorkWithMeta;
    use Macroable {
        __call as macroCall;
        __callStatic as macroCallStatic;
    }

    protected $instanceCache = [];

    public function postings(): HasMany
    {
        return $this->hasMany(config('walletable.models.posting'));
    }

    public function walletable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getAmountAttribute($value): Money
    {
        return new Money($value, $this->currency);
    }

    public function getBalanceAttribute(): Money
    {
        return Mutator::mutate(new WalletBalanceMutation(
            'wallet.balance',
            new Money(
                $this->getRawOriginal('amount'),
                $this->currency
            ),
            [
                'wallet' => $this,
            ]
        ))->value();
    }

    public function getCurrencyAttribute()
    {
        return Money::currency($this->getRawOriginal('currency'));
    }

    public function compatible(self $wallet): bool
    {
        return Walletable::compatible($this, $wallet);
    }

    /**
     * @deprecated Misspelling of compatible(); removed in the next major.
     */
    public function compactible(self $wallet): bool
    {
        trigger_error('Wallet::compactible() is deprecated; use Wallet::compatible().', E_USER_DEPRECATED);

        return $this->compatible($wallet);
    }

    /**
     * Transfer to another wallet. Posts a balanced transaction; returns it.
     *
     * Pass $idempotencyKey to make retries safe: a repeat call with the same key
     * returns the original transaction instead of transferring again.
     */
    public function transfer(
        self $wallet,
        $amount,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Transaction {
        $amount = $this->normaliseAmount($amount);
        return (new Transfer($this, $amount, $wallet, $remarks))
            ->idempotent($idempotencyKey)
            ->execute();
    }

    /**
     * Confirm a pending transaction that targets this wallet. Retrying a
     * confirmation is safe: an already-posted transaction is returned as-is.
     */
    public function confirm(Transaction $transaction): Transaction
    {
        if (!$transaction->isPending() && !$transaction->isPosted()) {
            throw new InvalidArgumentException('Only pending transactions can be confirmed.');
        }

        if (!$this->isALegOf($transaction)) {
            throw new InvalidArgumentException('Transaction does not affect this wallet.');
        }

        return (new Confirmation($this, $transaction))->execute();
    }

    /**
     * Whether this wallet is one of the transaction's legs: read from the parked
     * drafts while it is pending, and from the written postings once it is not.
     */
    protected function isALegOf(Transaction $transaction): bool
    {
        if (!$transaction->isPending()) {
            return $transaction->postings()->where('wallet_id', $this->getKey())->exists();
        }

        $raw = $transaction->getRawOriginal('draft_postings');
        $drafts = is_array($raw) ? $raw : (is_string($raw) ? json_decode($raw, true) : []);

        return collect($drafts ?? [])
            ->contains(fn($d) => (string)($d['wallet_id'] ?? '') === (string)$this->getKey());
    }

    public function unconfirmedCredit(
        $amount,
        ?string $title = null,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Transaction {
        $amount = $this->normaliseAmount($amount);
        return (new UnconfirmedCreditDebit('credit', $this, $amount, $title, $remarks))
            ->idempotent($idempotencyKey)
            ->execute();
    }

    public function unconfirmedDebit(
        $amount,
        ?string $title = null,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Transaction {
        $amount = $this->normaliseAmount($amount);
        return (new UnconfirmedCreditDebit('debit', $this, $amount, $title, $remarks))
            ->idempotent($idempotencyKey)
            ->execute();
    }

    /**
     * Pass $idempotencyKey to make retries safe: a repeat call with the same key
     * returns the original transaction instead of crediting again.
     */
    public function credit(
        $amount,
        ?string $title = null,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Transaction {
        $amount = $this->normaliseAmount($amount);
        return (new CreditDebit('credit', $this, $amount, $title, $remarks))
            ->idempotent($idempotencyKey)
            ->execute();
    }

    /**
     * Pass $idempotencyKey to make retries safe: a repeat call with the same key
     * returns the original transaction instead of debiting again.
     */
    public function debit(
        $amount,
        ?string $title = null,
        ?string $remarks = null,
        ?string $idempotencyKey = null
    ): Transaction {
        $amount = $this->normaliseAmount($amount);
        return (new CreditDebit('debit', $this, $amount, $title, $remarks))
            ->idempotent($idempotencyKey)
            ->execute();
    }

    public function money(int $amount): Money
    {
        return new Money($amount, $this->currency);
    }

    public function action(string $action): Action
    {
        if (isset($this->instanceCache['actions'][$action])) {
            return $this->instanceCache['actions'][$action];
        }

        return $this->instanceCache['actions'][$action] = new Action(
            $this,
            App::make(WalletableManager::class)->action($action)
        );
    }

    protected function normaliseAmount($amount): Money
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException(
                'Amount must be of type ' . Money::class . ' or Integer'
            );
        }
        return is_int($amount) ? $this->money($amount) : $amount;
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
