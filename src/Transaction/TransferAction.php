<?php

namespace Walletable\Transaction;

use Closure;
use Walletable\Internals\Actions\ActionData;
use Walletable\Internals\Actions\ActionInterface;
use Walletable\Ledger\PostingDraft;
use Walletable\Models\Posting;
use Walletable\Models\Wallet;

class TransferAction implements ActionInterface
{
    /**
     * Optional resolver for the posting's method resource (overrides the
     * default of returning the persisted morph target).
     */
    protected static ?Closure $methodResourceUsing = null;

    public function apply(PostingDraft $posting, ActionData $data)
    {
        $sender = $data->argument(0)->isA(Wallet::class)->value();
        $receiver = $data->argument(1)->isA(Wallet::class)->value();

        $counterparty = $posting->isCredit() ? $sender : $receiver;

        $posting->setAction('transfer');
        $posting->method(
            $counterparty->walletable->getMorphClass(),
            (string) $counterparty->walletable->getKey()
        );
    }

    public function title(Posting $posting)
    {
        return $posting->method ? $posting->method->getOwnerName() : null;
    }

    public function image(Posting $posting)
    {
        return $posting->method ? $posting->method->getOwnerImage() : null;
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
        if (static::$methodResourceUsing) {
            return call_user_func_array(static::$methodResourceUsing, [$this, $posting]);
        }

        return $posting->method;
    }

    /**
     * Override how methodResource() resolves the resource for a posting.
     */
    public static function methodResourceUsing(Closure $closure): void
    {
        static::$methodResourceUsing = $closure;
    }
}
