<?php

namespace Walletable\Internals\Lockers;

use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

class OptimisticLocker implements LockerInterface
{
    /**
     * {@inheritdoc}
     */
    public function creditLock(Wallet $wallet, Money $amount, Transaction $transaction)
    {
        $updated = false;
        do {
            $wallet->refresh();
            $query = config('walletable.models.wallet')::whereId($wallet->getKey())
                ->whereAmount($wallet->amount->value());

            $updated = $query->update([
                'amount' => ($balance = $wallet->amount->add($amount))->integer()
            ]);
            $transaction->forceFill([
                'amount' => $amount->value(),
                'balance' => $balance->value(),
            ]);
        } while (!$updated);
        $wallet->amount = $balance->integer();

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function debitLock(Wallet $wallet, Money $amount, Transaction $transaction)
    {
        $updated = false;
        do {
            $wallet->refresh();
            $query = config('walletable.models.wallet')::whereId($wallet->getKey())
                ->whereAmount($wallet->amount->value());

            $updated = $query->update([
                'amount' => ($balance = $wallet->amount->subtract($amount))->integer()
            ]);
            $transaction->forceFill([
                'amount' => $amount->value(),
                'balance' => $balance->value(),
            ]);
        } while (!$updated);
        $wallet->amount = $balance->integer();

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function shouldInitiateTransaction(Wallet $wallet, Money $amount, Transaction $transaction)
    {
        return false;
    }
}
