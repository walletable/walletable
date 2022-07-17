<?php

namespace Walletable\Transaction;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

class CreditDebitAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionData $data)
    {
        $data->argument(0)->isA(Wallet::class);

        $title = $data->argument(1)->type('string')->value(
            $transaction->type === 'credit' ? 'Credit' : 'Debit'
        );

        $transaction->forceFill([
            'action' => 'credit_debit'
        ])->meta('title', $title);
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return $transaction->meta('title');
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
