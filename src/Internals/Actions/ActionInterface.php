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
     * Check if the action supports debit
     *
     * @return bool
     */
    public function supportDebit(): bool;

    /**
     * Check if the action supports credit
     *
     * @return bool
     */
    public function supportCredit(): bool;

    /**
     * Check if the action reversal
     *
     * @return bool
     */
    public function reversable(Transaction $transaction): bool;

    /**
     * Hook to the reversal to perform extra tasks
     *
     * @return self
     */
    public function reverse(Transaction $transaction, Transaction $new): self;

    /**
     * Get the resource or a transaction method
     *
     * @return mixed
     */
    public function methodResource(Transaction $transaction);
}
