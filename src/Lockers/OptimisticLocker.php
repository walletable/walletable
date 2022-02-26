<?php

namespace Walletable\Lockers;

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
                    ->whereAmount($wallet->amount->getAmount());

                $updated = $query->update([
                    'amount' => ($balance = $wallet->amount->add($amount))->getInt()
                ]);
                $transaction->forceFill([
                    'amount' => $amount->getAmount(),
                    'balance' => $balance->getAmount(),
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
                    ->whereAmount($wallet->amount->getAmount());

                $updated = $query->update([
                    'amount' => ($balance = $wallet->amount->subtract($amount))->getInt()
                ]);
                $transaction->forceFill([
                    'amount' => $amount->getAmount(),
                    'balance' => $balance->getAmount(),
                ]);
            } while (!$updated);
        });

        return $updated;
    }
}
