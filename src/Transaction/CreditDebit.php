<?php

namespace Walletable\Transaction;

use InvalidArgumentException;
use Walletable\Facades\Walletable;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Internals\Actions\AppliesToTransaction;
use Walletable\Ledger\PostTransaction;
use Walletable\Ledger\TransactionDraft;
use Walletable\Models\HouseAccount;
use Walletable\Models\Posting;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

/**
 * Single-leg credit or debit. Posts a 2-line transaction: one against the
 * user wallet, the matching opposite-direction leg against the house wallet
 * for that currency.
 */
class CreditDebit
{
    protected string $type;
    protected Wallet $wallet;
    protected Money $amount;
    protected ?string $title;
    protected ?string $remarks;

    protected ?ActionInterface $action = null;
    protected ?ActionData $actionData = null;
    protected ?string $idempotencyKey = null;

    public function __construct(
        string $type,
        Wallet $wallet,
        Money $amount,
        ?string $title = null,
        ?string $remarks = null
    ) {
        if (!in_array($type, ['credit', 'debit'], true)) {
            throw new InvalidArgumentException('Argument 1 value can only be "credit" or "debit"');
        }

        $this->type = $type;
        $this->wallet = $wallet;
        $this->amount = $amount;
        $this->title = $title;
        $this->remarks = $remarks;
    }

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
     * De-duplicate this operation under the given key: a repeat call returns the
     * transaction posted by the first one instead of moving money again.
     */
    public function idempotent(?string $key): self
    {
        $this->idempotencyKey = $key;

        return $this;
    }

    public function execute(): Transaction
    {
        $action = $this->action ?? Walletable::action('credit_debit');

        if (!$action->{'support' . ucfirst($this->type)}()) {
            throw new \RuntimeException(sprintf(
                'Action %s does not support %s operations',
                get_class($action),
                $this->type
            ));
        }

        $currency = $this->wallet->getRawOriginal('currency');
        $house = HouseAccount::walletFor($currency);

        $direction = $this->type === 'credit'
            ? Posting::DIRECTION_CREDIT
            : Posting::DIRECTION_DEBIT;

        $draft = TransactionDraft::singleLeg(
            $this->wallet,
            $house,
            $direction,
            $this->amount,
            'credit_debit',
            $this->remarks
        )->idempotent($this->idempotencyKey);

        $data = $this->actionData ?? new ActionData($this->wallet, $this->title);

        // The user-wallet leg is the first posting; let the action customise it.
        $action->apply($draft->postings[0], $data);
        // Mirror the action tag onto the house leg for consistent reporting.
        $draft->postings[1]->setAction($draft->postings[0]->action);

        if ($action instanceof AppliesToTransaction) {
            $action->applyToTransaction($draft, $data);
        }

        return (new PostTransaction())->execute($draft);
    }
}
