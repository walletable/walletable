<?php

namespace Walletable\Wallet\Transaction;

use Illuminate\Support\Traits\ForwardsCalls;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

class TransactionBag
{
    use ForwardsCalls;

    /**
     * Transactions collection
     *
     * @var \Illuminate\Support\Collection
     */
    protected $transactions;

    public function __construct(?Transaction ...$transactions)
    {
        $this->transactions = \collect($transactions ?? []);
    }

    /**
     * Create new transaction
     *
     * @param \Walletable\Models\Wallet $wallet
     * @param array $data
     *
     * @return \Walletable\Models\Transaction
     */
    public function new(Wallet $wallet, array $data)
    {
        $trasanction = app(config('walletable.models.transaction'))->forceFill([
            'wallet_id' => $wallet->getKey(),
            'currency' => $wallet->getRawOriginal('currency')
        ] + $data);
        $this->transactions->add($trasanction);
        return $trasanction;
    }

    /**
     * Add new transaction
     *
     * @param \Walletable\Models\Transaction $trsanction
     * @return self
     */
    public function add(Transaction $transaction)
    {
        $this->transactions->add($transaction);
        return $this;
    }

    /**
     * Save all transactions in this bag
     *
     * @return void;
     */
    public function save()
    {
        $this->transactions->each(function (Transaction $trasanction) {
            $trasanction->save();
        });
    }

    /**
     * Map method calls to the collection
     *
     * @param string $method
     * @param array $parameters
     */
    public function __call(string $method, array $parameters)
    {
        return $this->forwardCallTo($this->transactions, $method, $parameters);
    }
}
