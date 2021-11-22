<?php

namespace Walletable\Wallet\Transaction;

use Symfony\Component\Translation\TranslatorBag;
use Walletable\Actions\ActionDataInterfare;
use Walletable\Models\Wallet;
use Walletable\Money\Money;

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

    /**
     * Amount to transfer
     *
     * @var \Walletable\Money\Money
     */
    //public $amount;

    /**
     * Trasanction bads
     *
     * @var \Walletable\Wallet\Transaction\TransactionBag
     */
    //public $bag;

    /**
     * The session id of the transfer
     *
     * @var bool
     */
    //public $session;

    public function __construct(Wallet $sender, Wallet $receiver/* , Money $amount, TranslatorBag $bag, string $session */) {
        $this->sender = $sender;
        $this->receiver = $receiver;
        /* $this->amount = $amount;
        $this->bag = $bag;
        $this->session = $session; */
    }
}
