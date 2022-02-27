<?php

namespace Walletable\Transaction;

use Walletable\Internals\Actions\ActionDataInterfare;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;
use Walletable\Internals\Details\Info;
use Walletable\Internals\Details\Section;

class TransferAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionDataInterfare $data)
    {
        if ($transaction->type == 'credit') {
            $transaction->forceFill([
                'action' => 'transfer',
                'method_id' => $data->sender->getKey(),
                'method_type' => $data->sender->getMorphClass()
            ]);
        }

        if ($transaction->type == 'debit') {
            $transaction->forceFill([
                'action' => 'transfer',
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

    /**
     * {@inheritdoc}
     */
    public function image(Transaction $transaction)
    {
        return $transaction->method->walletable->getOwnerImage();
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
