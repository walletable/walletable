<?php

namespace Walletable\Internals\Actions;

use Walletable\Models\Transaction;

interface ActionInterface
{
    /**
     * Apply the action to the transaction before saving
     *
     * @param \Walletable\Models\Transaction $transaction The transaction
     * @param \Walletable\Internals\Actions\ActionData $data Data from the Operation
     */
    public function apply(Transaction $transaction, ActionData $data);

    /**
     * Returns the title of the transaction
     *
     * @param \Walletable\Models\Transaction $transaction The transaction
     */
    public function title(Transaction $transaction);

    /**
     * Returns the title of the transaction
     *
     * @param \Walletable\Models\Transaction $transaction The transaction
     *
     * @return string
     */
    public function image(Transaction $transaction);

    /**
     * Returns the details of the transaction
     *
     * @param \Walletable\Models\Transaction $transaction The transaction
     *
     * @return string
     */
    public function details(Transaction $transaction);

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
