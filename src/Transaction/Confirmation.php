<?php

namespace Walletable\Transaction;

use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Walletable\Events\ConfirmedTransaction;
use Walletable\Events\CreatedTransaction;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Walletable;
use Walletable\Internals\Lockers\LockerInterface;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

class Confirmation
{
    /**
     * Transfer status
     *
     * @var bool
     */
    protected $successful = false;

    /**
     * Sender wallet
     *
     * @var \Walletable\Models\Wallet
     */
    protected $wallet;

    /**
     * Sender wallet
     *
     * @var \Walletable\Models\Transaction
     */
    protected $transaction;

    /**
     * The transfer locker
     *
     * @var \Walletable\Internals\Lockers\OptimisticLocker
     */
    protected $locker;

    /**
     * Execution Options
     *
     * @var array
     */
    protected $options;

    public function __construct(
        Wallet $wallet,
        Transaction $transaction,
        LockerInterface|null $locker = null,
    ) {
        $this->wallet = $wallet;
        $this->transaction = $transaction;
        $this->locker = $locker;
    }

    /**
     * Execute the transfer
     *
     * @return self
     */
    public function execute(): self
    {
        $this->checks();
        $locker = $this->locker();
        $shouldInitiateTransaction = $locker->shouldInitiateTransaction($this->wallet, $this->transaction->amount, $this->transaction) ||
            ($this->options['should_initiate_transaction'] ?? false);

        try {
            $method = $this->transaction->type . 'Lock';
            $action = Walletable::action($this->transaction->getRawOriginal('action') ?? 'credit_debit');

            if (!$action->{'support' . ucfirst($this->transaction->type)}()) {
                throw new Exception('This action does not support ' . $this->transaction->type . ' operations', 1);
            }

            if ($shouldInitiateTransaction) {
                DB::beginTransaction();
            }

            if ($this->locker()->$method($this->wallet, $this->transaction->amount, $this->transaction)) {
                $this->successful = true;

                $this->transaction->forceFill([
                    'confirmed' => true,
                    'confirmed_at' => now(),
                    'status' => 'completed'
                ])->save();
                App::make('events')->dispatch(new ConfirmedTransaction(
                    $this->transaction
                ));
            }

            if ($shouldInitiateTransaction) {
                DB::commit();
            }
        } catch (\Throwable $th) {
            if ($shouldInitiateTransaction) {
                DB::rollBack();
            }
            throw $th;
        }

        return $this;
    }

    /**
     * Run some compulsory checks
     *
     * @return void
     */
    protected function checks()
    {
        if (
            $this->transaction->type === 'debit' &&
            $this->wallet->amount->lessThan($this->transaction->amount)
        ) {
            throw new InsufficientBalanceException($this->wallet, $this->transaction->amount);
        }
    }

    /**
     * Get transaction bag
     *
     * @return \Walletable\Models\Transaction
     */
    public function transaction(): Transaction
    {
        return $this->transaction;
    }

    /**
     * Get amount
     *
     * @return \Walletable\Money\Money
     */
    public function getAmount(): Money
    {
        return $this->transaction->amount;
    }

    /**
     * Get the locker for the transfer
     */
    protected function locker(): LockerInterface
    {
        if ($this->locker) {
            return $this->locker;
        }
        return $this->locker = Walletable::locker(config('walletable.locker'));
    }
}
