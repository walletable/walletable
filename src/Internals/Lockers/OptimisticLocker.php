<?php

namespace Walletable\Internals\Lockers;

use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use (&$updated, $wallet, $amount, $transaction) {
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
        });

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function debitLock(Wallet $wallet, Money $amount, Transaction $transaction)
    {
        $updated = false;
        DB::transaction(function () use (&$updated, $wallet, $amount, $transaction) {
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
        });

        return $updated;
    }
}
