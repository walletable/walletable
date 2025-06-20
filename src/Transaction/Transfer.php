<?php

namespace Walletable\Transaction;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Walletable\Events\ConfirmedTransaction;
use Walletable\Events\CreatedTransaction;
use Walletable\Exceptions\IncompactibleWalletsException;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Walletable;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Lockers\LockerInterface;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

class Transfer
{
    /**
     * Sender wallet
     *
     * @var \Walletable\Models\Wallet
     */
    protected $sender;

    /**
     * Receiver wallet
     *
     * @var \Walletable\Models\Wallet
     */
    protected $receiver;

    /**
     * Amount to transfer
     *
     * @var \Walletable\Money\Money
     */
    protected $amount;

    /**
     * Trasanction bads
     *
     * @var \Walletable\Transaction\TransactionBag
     */
    protected $bag;

    /**
     * Transfer status
     *
     * @var bool
     */
    protected $successful = false;

    /**
     * Note added to the transfer
     *
     * @var string|null
     */
    protected $remarks;

    /**
     * The session id of the transfer
     *
     * @var bool
     */
    protected $session;

    /**
     * The transfer locker
     *
     * @var \Walletable\Internals\Lockers\OptimisticLocker
     */
    protected $locker;

    public function __construct(Wallet $sender, Money $amount, Wallet $receiver, string $remarks = null)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->amount = $amount;
        $this->remarks = $remarks;
        $this->session = Str::uuid();
        $this->bag = new TransactionBag();
    }

    /**
     * Execute the transfer
     *
     * @return self
     */
    public function execute(): self
    {
        $this->checks();

        try {
            DB::beginTransaction();

            if ($this->debitSender()) {
                $transaction = $this->bag->new($this->receiver, [
                    'type' => 'credit',
                    'session' => $this->session,
                    'remarks' => $this->remarks
                ]);

                if ($this->locker()->creditLock($this->receiver, $this->amount, $transaction)) {
                    $this->successful = true;

                    Walletable::applyAction('transfer', $this->bag, new ActionData(
                        $this->sender,
                        $this->receiver
                    ));
                    $this->bag->each(function ($item) {
                        $item->forceFill([
                            'created_at' => now(),
                            'confirmed' => true,
                            'confirmed_at' => now(),
                            'status' => 'completed'
                        ])->save();
                        App::make('events')->dispatch(new ConfirmedTransaction(
                            $item
                        ));
                        App::make('events')->dispatch(new CreatedTransaction(
                            $item
                        ));
                    });
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        return $this;
    }

    /**
     * Debit the sender
     */
    protected function debitSender()
    {
        $transaction = $this->bag->new($this->sender, [
            'type' => 'debit',
            'session' => $this->session,
            'remarks' => $this->remarks
        ]);

        if ($this->locker()->debitLock($this->sender, $this->amount, $transaction)) {
            return true;
        }
    }

    /**
     * Run some compulsory checks
     *
     * @return void
     */
    protected function checks()
    {
        if ($this->sender->amount->lessThan($this->amount)) {
            throw new InsufficientBalanceException($this->sender, $this->amount);
        }

        if (!$this->sender->compactible($this->receiver)) {
            throw new IncompactibleWalletsException($this->sender, $this->receiver);
        }
    }

    /**
     * Get transaction bag
     *
     * @return \Walletable\Transaction\TransactionBag
     */
    public function getTransactions(): TransactionBag
    {
        return $this->bag;
    }

    /**
     * Get the senders transaction
     *
     * @return Transaction
     */
    public function out(): Transaction
    {
        return $this->bag->where('type', 'debit')->first();
    }

    /**
     * Get the reciepient`s transaction
     *
     * @return Transaction
     */
    public function in(): Transaction
    {
        return $this->bag->where('type', 'credit')->first();
    }

    /**
     * Get amount
     *
     * @return \Walletable\Money\Money
     */
    public function getAmount(): Money
    {
        return $this->amount;
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

    /**
     * Check is the transfer was successful
     *
     * @return boolean
     */
    public function successful(): bool
    {
        return $this->successful;
    }
}
