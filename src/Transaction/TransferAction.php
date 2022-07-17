<?php

namespace Walletable\Transaction;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

class TransferAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionData $data)
    {
        $sender = $data->argument(0)->isA(Wallet::class)->value();
        $receiver = $data->argument(1)->isA(Wallet::class)->value();

        if ($transaction->type == 'credit') {
            $transaction->forceFill([
                'action' => 'transfer',
                'method_id' => $sender->getKey(),
                'method_type' => $sender->getMorphClass()
            ]);
        }

        if ($transaction->type == 'debit') {
            $transaction->forceFill([
                'action' => 'transfer',
                'method_id' => $receiver->getKey(),
                'method_type' => $receiver->getMorphClass()
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
    public function supportDebit(): bool
    {
        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function supportCredit(): bool
    {
        return true;
    }
}
