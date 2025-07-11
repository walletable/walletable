<?php

namespace Walletable\Traits;

use Illuminate\Support\Facades\DB;
use Walletable\Internals\Actions\ActionData;
use Walletable\Models\Transaction;

trait BatchTransactionable
{
    /**
     * Start a batch transaction operation
     *
     * @param callable $callback
     * @return mixed
     */
    public function batchTransaction(callable $callback)
    {
        return DB::transaction(function () use ($callback) {
            return $callback($this);
        });
    }

    /**
     * Create multiple credit transactions in a batch
     *
     * @param array $transactions Array of transaction data [amount, data, remarks, meta]
     * @param string $action Action name (default: 'credit_debit')
     * @return \Illuminate\Support\Collection
     */
    public function batchCredit(array $transactions, string $action = 'credit_debit')
    {
        return $this->batchTransaction(function ($wallet) use ($transactions, $action) {
            return collect($transactions)->map(function ($transaction) use ($wallet, $action) {
                return $wallet->action($action)->credit(
                    $transaction['amount'],
                    $transaction['data'] ?? new ActionData('batch_credit'),
                    $transaction['remarks'] ?? 'Batch credit transaction',
                    $transaction['meta'] ?? []
                );
            });
        });
    }

    /**
     * Create multiple debit transactions in a batch
     *
     * @param array $transactions Array of transaction data [amount, data, remarks, meta]
     * @param string $action Action name (default: 'credit_debit')
     * @return \Illuminate\Support\Collection
     */
    public function batchDebit(array $transactions, string $action = 'credit_debit')
    {
        return $this->batchTransaction(function ($wallet) use ($transactions, $action) {
            return collect($transactions)->map(function ($transaction) use ($wallet, $action) {
                return $wallet->action($action)->debit(
                    $transaction['amount'],
                    $transaction['data'] ?? new ActionData('batch_debit'),
                    $transaction['remarks'] ?? 'Batch debit transaction',
                    $transaction['meta'] ?? []
                );
            });
        });
    }
}
