<?php

namespace Walletable\Transaction;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;

class CreditDebitAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionData $data)
    {
        $title = $data->argument(0)->type('type')->value(
            $transaction->type === 'credit' ? 'Credit' : 'Debit'
        );

        $transaction->forceFill([
            'action' => 'credit_debit'
        ])->data('title', $title);
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return $transaction->data('title');
    }

    /**
     * {@inheritdoc}
     */
    public function image(Transaction $transaction)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function details(Transaction $transaction)
    {
        return \collect([]);
    }

    /**
     * {@inheritdoc}
     */
    public function suppportDebit(): bool
    {
        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function suppportCredit(): bool
    {
        return true;
    }
}
