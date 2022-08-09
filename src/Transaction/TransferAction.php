<?php

namespace Walletable\Transaction;

use Closure;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Models\Transaction;
use Walletable\Models\Wallet;

class TransferAction implements ActionInterface
{
    /**
     * Custom closure to get resource of a transaction method
     *
     * @var \Closure
     */
    protected static $methodResourceUsing;

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
                'method_id' => $sender->walletable->getKey(),
                'method_type' => $sender->walletable->getMorphClass()
            ]);
        }

        if ($transaction->type == 'debit') {
            $transaction->forceFill([
                'action' => 'transfer',
                'method_id' => $receiver->walletable->getKey(),
                'method_type' => $receiver->walletable->getMorphClass()
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function title(Transaction $transaction)
    {
        return $transaction->method->getOwnerName();
    }

    /**
     * {@inheritdoc}
     */
    public function image(Transaction $transaction)
    {
        return $transaction->method->getOwnerImage();
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
    public function reverse(Transaction $transaction, Transaction $new): self
    {
        return $this;
    }

    /**
     * Get the resource of a transaction method
     *
     * @return mixeda
     */
    public function methodResource(Transaction $transaction)
    {
        if (static::$methodResourceUsing) {
            return call_user_func_array(static::$methodResourceUsing, [$this, $transaction]);
        }

        return $transaction->method;
    }

    /**
     * Get the resource of a transaction method using closure
     *
     * @param Closure $closure
     * @return void
     */
    public static function methodResourceUsing(Closure $closure)
    {
        static::$methodResourceUsing = $closure;
    }
}
