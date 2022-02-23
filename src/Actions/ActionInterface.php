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

    /**
     * Returns the title of the transaction
     *
     * @param \Walletable\Models\Transaction $transaction The trasanction
     */
    public function title(Transaction $transaction);

    /**
     * Check if the action supports debit
     *
     * @return bool
     */
    public function suppportDebit(): bool;

    /**
     * Check if the action supports credit
     *
     * @return bool
     */
    public function suppportCredit(): bool;
}