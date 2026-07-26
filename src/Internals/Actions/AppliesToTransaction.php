<?php

namespace Walletable\Internals\Actions;

use Walletable\Ledger\TransactionDraft;

/**
 * Optional companion to ActionInterface.
 *
 * ActionInterface::apply() runs once per posting (per leg). An action that also
 * implements this interface gets one shot at the transaction header before the
 * row is inserted: narration, reference, meta, and any extra columns the
 * application declared with Walletable::extendTransaction().
 */
interface AppliesToTransaction
{
    /**
     * Decorate the transaction draft. Called once per transaction, after every
     * posting has been applied.
     */
    public function applyToTransaction(TransactionDraft $draft, ActionData $data): void;
}
