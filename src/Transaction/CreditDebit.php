<?php

namespace Walletable\Transaction;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Exceptions\InsufficientBalanceException;
use Walletable\Facades\Walletable;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Lockers\LockerInterface;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

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

    /**
     * Execution Options
     *
     * @var array
     */
    protected $options;

    public function __construct(
        string $type,
        Wallet $wallet,
        Money $amount,
        string $title = null,
        string $remarks = null,
        LockerInterface $locker = null,
        array $options = []
    ) {
        if (!in_array($type, ['credit', 'debit'])) {
            throw new InvalidArgumentException('Argument 1 value can only be "credit" or "debit"');
        }

        $this->type = $type;
        $this->wallet = $wallet;
        $this->amount = $amount;
        $this->title = $title;
        $this->remarks = $remarks;
        $this->locker = $locker;
        $this->options = $options;
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
        $locker = $this->locker();
        $transaction = $this->bag->new($this->wallet, [
            'type' => $this->type,
            'session' => $this->session,
            'remarks' => $this->remarks
        ]);
        $shouldInitiateTransaction = $locker->shouldInitiateTransaction($this->wallet, $this->amount, $transaction) ||
            ($this->options['should_initiate_transaction'] ?? false);

        try {
            $method = $this->type . 'Lock';
            $action = $this->action ?? Walletable::action('credit_debit');

            if (!$action->{'support' . ucfirst($this->type) }()) {
                throw new Exception('This action does not support ' . $this->type . ' operations', 1);
            }

            if ($shouldInitiateTransaction) {
                DB::beginTransaction();
            }

            if ($this->locker()->$method($this->wallet, $this->amount, $transaction)) {
                $this->successful = true;

                Walletable::applyAction(
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
            $action = Walletable::action($action);
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
        return $this->locker = Walletable::locker(config('walletable.locker'));
    }
}
