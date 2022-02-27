<?php

namespace Walletable\Transaction;

use Walletable\Internals\Actions\ActionDataInterfare;
use Walletable\Models\Wallet;

class TransferData implements ActionDataInterfare
{
    /**
     * Sender wallet
     *
     * @var \Walletable\Models\Wallet
     */
    public $sender;

    /**
     * Receiver wallet
     *
     * @var \Walletable\Models\Wallet
     */
    public $receiver;

    public function __construct(Wallet $sender, Wallet $receiver)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
    }
}
