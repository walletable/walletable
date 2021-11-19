<?php

namespace Walletable\Services\Transaction;

use Walletable\Actions\ActionDataInterfare;
use Walletable\Actions\ActionInterface;
use Walletable\Models\Transaction;

class CreditDebitAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionDataInterfare $data)
    {
        $title = $transaction->type === 'credit' ? 'Credit' : 'Debit';
        $transaction->data('title', $data->title ?? $title);
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return $transaction->data('title');
    }
}
