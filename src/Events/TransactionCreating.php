<?php

namespace Walletable\Events;

use Illuminate\Queue\SerializesModels;
use Walletable\Ledger\TransactionDraft;

/**
 * Fired before PostTransaction persists a draft. Listeners receive the
 * mutable draft and may decorate meta/narration/reference before the row
 * is inserted (no postings are written yet at this point).
 */
class TransactionCreating
{
    use SerializesModels;

    public TransactionDraft $draft;

    public function __construct(TransactionDraft $draft)
    {
        $this->draft = $draft;
    }
}
