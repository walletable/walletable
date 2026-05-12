<?php

namespace Walletable\Internals\Actions;

use Walletable\Ledger\PostingDraft;
use Walletable\Models\Posting;

interface ActionInterface
{
    /**
     * Decorate a posting draft before it is persisted: set the action tag,
     * fill method_id/method_type, stash per-leg meta, etc.
     *
     * @param PostingDraft $posting The mutable posting draft.
     * @param ActionData $data Caller-supplied data (variadic ArgumentBag).
     */
    public function apply(PostingDraft $posting, ActionData $data);

    /**
     * Human-readable title of a persisted posting.
     */
    public function title(Posting $posting);

    /**
     * Image URL or asset reference for a persisted posting.
     *
     * @return string|null
     */
    public function image(Posting $posting);

    public function supportDebit(): bool;

    public function supportCredit(): bool;

    /**
     * Whether a posting is eligible to be reversed by this action.
     */
    public function reversable(Posting $posting): bool;

    /**
     * Hook into a reversal to perform extra work; called by the executor
     * with the original posting and the freshly-written opposite leg.
     */
    public function reverse(Posting $posting, Posting $new): ActionInterface;

    /**
     * Optional resource (e.g. counter-party model) attached to a posting.
     *
     * @return mixed
     */
    public function methodResource(Posting $posting);
}
