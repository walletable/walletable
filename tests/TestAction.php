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

    /**
     * Check if the action reversal
     *
     * @return bool
     */
    public function reversable(Transaction $transaction): bool
    {
        return false;
    }

    /**
     * Hook to the reversal to perform extra tasks
     *
     * @return self
     */
    public function reverse(Transaction $transaction, Transaction $new): ActionInterface
    {
        return $this;
    }

    /**
     * Get the resource or a transaction method
     *
     * @return mixed
     */
    public function methodResource(Transaction $transaction)
    {
        return null;
    }
}
