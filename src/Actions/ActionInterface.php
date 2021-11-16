<?php

namespace Walletable\Actions;

use Walletable\Models\Transaction;

interface ActionInterface
{
    /**
     * Apply the action to the transaction before saving
     *
     * @param \Walletable\Models\Transaction $transaction The trasanction
     * @param \Walletable\Actions\ActionDataInterfare $data Data from the Operation
     */
    public function apply(Transaction $transaction, ActionDataInterfare $data);
}