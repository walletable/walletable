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
    public function creditLock(Wallet $wallet, Money $amount, bool $confirmed, Transaction $transaction)
    {
        $updated = false;
        DB::transaction(function () use (&$updated, $wallet, $amount, $confirmed, $transaction) {
            do {
                $wallet->refresh();
                $query = config('walletable.models.wallet')::whereId($wallet->getKey())
                    ->whereAmount($wallet->amount->getAmount());

                $balance = $wallet->amount;
                if ($confirmed) {
                    $updated = $query->update([
                        'amount' => ($balance = $balance->add($amount))->getInt()
                    ]);
                } else {
                    $updated = true;
                }

                $transaction->forceFill([
                    'amount' => $amount->getAmount(),
                    'balance' => $balance->getAmount(),
                    'confirmed' => $confirmed,
                ]);
            } while (!$updated);
        });

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function debitLock(Wallet $wallet, Money $amount, bool $confirmed, Transaction $transaction)
    {
        $updated = false;
        DB::transaction(function () use (&$updated, $wallet, $amount, $confirmed, $transaction) {
            do {
                $wallet->refresh();
                $query = config('walletable.models.wallet')::whereId($wallet->getKey())
                    ->whereAmount($wallet->amount->getAmount());

                $balance = $wallet->amount;
                if ($confirmed) {
                    $updated = $query->update([
                        'amount' => ($balance = $balance->subtract($amount))->getInt()
                    ]);
                } else {
                    $updated = true;
                }

                $transaction->forceFill([
                    'amount' => $amount->getAmount(),
                    'balance' => $balance->getAmount(),
                    'confirmed' => $confirmed,
                ]);
            } while (!$updated);
        });

        return $updated;
    }
}
