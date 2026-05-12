<?php

namespace Walletable\Transaction;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Ledger\PostingDraft;
use Walletable\Models\Posting;
use Walletable\Models\Wallet;

class CreditDebitAction implements ActionInterface
{
    public function apply(PostingDraft $posting, ActionData $data)
    {
        $data->argument(0)->isA(Wallet::class);

        $title = $data->argument(1)->type('string')->value(
            $posting->isCredit() ? 'Credit' : 'Debit'
        );

        $posting->setAction('credit_debit');
        $posting->setMeta('title', $title);
    }

    public function title(Posting $posting)
    {
        return $posting->meta('title');
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
