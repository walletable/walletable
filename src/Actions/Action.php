<?php

namespace Walletable\Actions;

use InvalidArgumentException;
use Walletable\Models\Wallet;
use Walletable\Money\Money;
use Walletable\Wallet\Transaction\CreditDebit;

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
     * @var \Walletable\Actions\ActionInterface
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
     * @param \Walletable\Actions\ActionDataInterfare $data
     * @param string|null $remarks
     */
    public function credit($amount, ActionDataInterfare $data, string $remarks = null): CreditDebit
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException('Argument 1 must be of type ' . Money::class . ' or Integer');
        }

        if (is_int($amount)) {
            $amount = $this->wallet->money($amount);
        }

        if (isset($data->remarks) && is_string($data->remarks)) {
            $remarks = $data->remarks;
        }

        return (new CreditDebit('credit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }

    /**
     * Debit the wallet
     *
     * @param int|\Walletable\Money\Money $amount
     * @param \Walletable\Actions\ActionDataInterfare $data
     * @param string|null $remarks
     */
    public function debit($amount, ActionDataInterfare $data, string $remarks = null): CreditDebit
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException('Argument 1 must be of type ' . Money::class . ' or Integer');
        }

        if (is_int($amount)) {
            $amount = $this->wallet->money($amount);
        }

        if (isset($data->remarks) && is_string($data->remarks)) {
            $remarks = $data->remarks;
        }

        return (new CreditDebit('debit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }
}
