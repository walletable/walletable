<?php

namespace Walletable\Transaction;

use Exception;
use Illuminate\Support\Str;
use Walletable\Money\Money;
use InvalidArgumentException;
use Walletable\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Walletable\Facades\Wallet as Manager;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Internals\Lockers\LockerInterface;
use Walletable\Exceptions\InsufficientBalanceException;

class CreditDebit
{
    /**
     * Transaction type
     *
     * @var string
     */
    protected $type;

    /**
     * Sender wallet
     *
     * @var \Walletable\Models\Wallet
     */
    protected $wallet;

    /**
     * confirm transaction
     *
     * @var bool
     */
    protected $confirmed;


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
     * Title of the
     *
     * @var string|null
     */
    protected $title;

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

    /**
     * Action of the transaction
     *
     * @var \Walletable\Internals\Actions\ActionInterface
     */
    protected $action;

    /**
     * Action of the transaction
     *
     * @var \Walletable\Internals\Actions\ActionData
     */
    protected $actionData;

    public function __construct(
        string $type,
        Wallet $wallet,
        Money $amount,
        bool $confirmed = true,
        string $title = null,
        string $remarks = null
    ) {
        if (!in_array($type, ['credit', 'debit'])) {
            throw new InvalidArgumentException('Argument 1 value can only be "credit" or "debit"');
        }

        $this->type = $type;
        $this->wallet = $wallet;
        $this->amount = $amount;
        $this->confirmed = $confirmed;
        $this->title = $title;
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

            $transaction = $this->bag->new($this->wallet, [
                'type' => $this->type,
                'session' => $this->session,
                'remarks' => $this->remarks
            ]);

            $method = $this->type . 'Lock';
            $action = $this->action ?? Manager::action('credit_debit');

            if (!$action->{'support' . ucfirst($this->type) }()) {
                throw new Exception('This action does not support ' . $this->type . ' operations', 1);
            }

            if ($this->locker()->$method($this->wallet, $this->amount, $this->confirmed, $transaction)) {
                $this->successful = true;

                Manager::applyAction(
                    $action,
                    $this->bag,
                    $this->actionData ??  new ActionData(
                        $this->wallet,
                        $this->title
                    )
                );

                $this->bag->each(function ($item) {
                    $item->forceFill([
                        'created_at' => now()
                    ])->save();
                });
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

        return $this;
    }

    /**
     * Debit the wallet
     */
    protected function debitSender()
    {
        $transaction = $this->bag->new($this->wallet, [
            'type' => 'debit',
            'session' => $this->session,
            'remarks' => $this->remarks
        ]);

        if ($this->locker()->debitLock($this->wallet, $this->amount, $transaction)) {
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
        if (
            $this->type === 'debit' &&
            $this->wallet->amount->lessThan($this->amount)
        ) {
            throw new InsufficientBalanceException($this->wallet, $this->amount);
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
     * Get amount
     *
     * @return \Walletable\Money\Money
     */
    public function getAmount(): Money
    {
        return $this->amount;
    }

    /**
     * Set the action for the transaction
     *
     * @param \Walletable\Internals\Actions\ActionInterface|string $action
     * @param \Walletable\Internals\Actions\ActionData $actionData
     *
     * @return self
     */
    public function setAction($action, ActionData $actionData): self
    {
        if (!is_string($action) && !($action instanceof ActionInterface)) {
            throw new InvalidArgumentException(
                sprintf('Argument 1 must be of type %s or String', ActionInterface::class)
            );
        }

        if (is_string($action)) {
            $action = Manager::action($action);
        }

        $this->action = $action;
        $this->actionData = $actionData;

        return $this;
    }

    /**
     * Get the locker for the transfer
     */
    protected function locker(): LockerInterface
    {
        if ($this->locker) {
            return $this->locker;
        }
        return $this->locker = Manager::locker(config('walletable.locker'));
    }
}
