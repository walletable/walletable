<?php

namespace Walletable\Tests;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Ledger\PostingDraft;
use Walletable\Models\Posting;

class TestAction implements ActionInterface
{
    public function apply(PostingDraft $posting, ActionData $data)
    {
        $posting->setAction('test');
    }

    public function title(Posting $posting)
    {
        return 'Test Transaction';
    }

    public function image(Posting $posting)
    {
        return '/image/test/transaction.jpg';
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
