<?php

namespace Walletable\Internals\Actions;

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
     * @var \Walletable\Internals\Actions\ActionInterface
     */
    protected $action;

    public function __construct(Transaction $transaction, ActionInterface $action)
    {
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

    /**
     * Returns the image
     */
    public function image()
    {
        return $this->action->image($this->transaction);
    }

    /**
     * Returns the method resource
     */
    public function resource()
    {
        return $this->action->methodResource($this->transaction);
    }

    /**
     * Get the raw action object
     *
     * @return ActionInterface
     */
    public function getAction(): ActionInterface
    {
        return $this->action;
    }
}
