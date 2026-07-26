<?php

namespace Walletable\Tests;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Internals\Actions\AppliesToTransaction;
use Walletable\Ledger\PostingDraft;
use Walletable\Ledger\TransactionDraft;
use Walletable\Models\Posting;

/**
 * Action that decorates both the posting legs and the transaction header,
 * writing an application-declared column.
 */
class TestColumnAction implements ActionInterface, AppliesToTransaction
{
    public function apply(PostingDraft $posting, ActionData $data)
    {
        $posting->setAction('test_column');
    }

    public function applyToTransaction(TransactionDraft $draft, ActionData $data): void
    {
        $draft->set('channel', $data->argument(1)->type('string')->value('web'));
    }

    public function title(Posting $posting)
    {
        return 'Test Column Transaction';
    }

    public function image(Posting $posting)
    {
        return null;
    }

    public function supportDebit(): bool
    {
        return true;
    }

    public function supportCredit(): bool
    {
        return true;
    }

    public function reversable(Posting $posting): bool
    {
        return false;
    }

    public function reverse(Posting $posting, Posting $new): ActionInterface
    {
        return $this;
    }

    public function methodResource(Posting $posting)
    {
        return null;
    }
}
