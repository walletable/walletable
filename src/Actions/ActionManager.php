<?php

namespace Walletable\Actions;

use Walletable\Models\Transaction;

class ActionManager
{
    /**
     * The transaction
     *
     * @var \Walletable\Models\Transaction
     */
    protected $transaction;

    /**
     * The transaction
     *
     * @var \Walletable\Actions\ActionInterface
     */
    protected $action;

    public function __construct(Transaction $transaction, ActionInterface $action) {
        $this->transaction = $transaction;
        $this->action = $action;
    }

    /**
     * Returns the title
     */
    public function title()
    {
        return $this->action->title($this->transaction);
    }
}
