<?php

namespace Walletable\Internals\Actions;

use InvalidArgumentException;
use Walletable\Models\Wallet;
use Walletable\Money\Money;
use Walletable\Internals\Actions\ActionData;
use Walletable\Transaction\CreditDebit;

class Action
{
    /**
     * Wallet
     *
     * @var \Walletable\Models\Wallet
     */
    protected $wallet;

    /**
     * The action
     *
     * @var \Walletable\Internals\Actions\ActionInterface
     */
    protected $action;

    public function __construct(Wallet $wallet, ActionInterface $action)
    {
        $this->wallet = $wallet;
        $this->action = $action;
    }

    /**
     * Credit the wallet
     *
     * @param int|\Walletable\Money\Money $amount
     * @param \Walletable\Internals\Actions\ActionData $data
     * @param string|null $remarks
     */
    public function credit($amount, ActionData $data, string $remarks = null): CreditDebit
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException(sprintf('Argument 1 must be of type %s or Integer', Money::class));
        }

        if (is_int($amount)) {
            $amount = $this->wallet->money($amount);
        }

        return (new CreditDebit('credit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }

    /**
     * Debit the wallet
     *
     * @param int|\Walletable\Money\Money $amount
     * @param \Walletable\Internals\Actions\ActionData $data
     * @param string|null $remarks
     */
    public function debit($amount, ActionData $data, string $remarks = null): CreditDebit
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException(sprintf('Argument 1 must be of type %s or Integer', Money::class));
        }

        if (is_int($amount)) {
            $amount = $this->wallet->money($amount);
        }

        return (new CreditDebit('debit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }
}
