<?php

namespace Walletable\Tests;

use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;

class TestAction implements ActionInterface
{
    /**
     * {@inheritdoc}
     */
    public function apply(Transaction $transaction, ActionData $data)
    {
        //
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return 'Test Transaction';
    }

    /**
     * {@inheritdoc}
     */
    public function image(Transaction $transaction)
    {
        return '/image/test/transaction.jpg';
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
