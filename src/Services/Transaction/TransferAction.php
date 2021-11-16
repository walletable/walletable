<?php

namespace Walletable\Services\Transaction;

use Walletable\Actions\ActionDataInterfare;
use Walletable\Actions\ActionInterface;
use Walletable\Models\Transaction;

class TransferAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionDataInterfare $data)
    {
        if ($transaction->type == 'credit') {
            $transaction->forceFill([
                'method_id' => $data->sender->getKey(),
                'method_type' => $data->sender->getMorphClass()
            ]);
        }

        if ($transaction->type == 'debit') {
            $transaction->forceFill([
                'method_id' => $data->receiver->getKey(),
                'method_type' => $data->receiver->getMorphClass()
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return $transaction->method->walletable->getOwnerName();
    }
}
