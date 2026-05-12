<?php

namespace Walletable\Internals\Actions;

use InvalidArgumentException;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;
use Walletable\Transaction\CreditDebit;
use Walletable\Transaction\UnconfirmedCreditDebit;

/**
 * Fluent helper returned by Wallet::action($name). Lets callers run a credit/
 * debit (or its unconfirmed variant) under a custom action with an
 * ActionData payload, without dropping down to the executor classes.
 */
class Action
{
    protected Wallet $wallet;
    protected ActionInterface $action;

    public function __construct(Wallet $wallet, ActionInterface $action)
    {
        $this->wallet = $wallet;
        $this->action = $action;
    }

    public function unconfirmedCredit($amount, ActionData $data, ?string $remarks = null): Transaction
    {
        $amount = $this->normalise($amount);

        return (new UnconfirmedCreditDebit('credit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }

    public function unconfirmedDebit($amount, ActionData $data, ?string $remarks = null): Transaction
    {
        $amount = $this->normalise($amount);

        return (new UnconfirmedCreditDebit('debit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }

    public function credit($amount, ActionData $data, ?string $remarks = null): Transaction
    {
        $amount = $this->normalise($amount);

        return (new CreditDebit('credit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }

    public function debit($amount, ActionData $data, ?string $remarks = null): Transaction
    {
        $amount = $this->normalise($amount);

        return (new CreditDebit('debit', $this->wallet, $amount, null, $remarks))
            ->setAction($this->action, $data)
            ->execute();
    }

    public function getAction(): ActionInterface
    {
        return $this->action;
    }

    protected function normalise($amount): Money
    {
        if (!is_int($amount) && !($amount instanceof Money)) {
            throw new InvalidArgumentException(
                sprintf('Argument 1 must be of type %s or Integer', Money::class)
            );
        }
        return is_int($amount) ? $this->wallet->money($amount) : $amount;
    }
}
