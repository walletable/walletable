<?php

namespace Walletable\Wallet\Transaction;

use Walletable\Actions\ActionDataInterfare;
use Walletable\Models\Wallet;

class CreditDebitData implements ActionDataInterfare
{
    /**
     * Wallet
     *
     * @var \Walletable\Models\Wallet
     */
    public $wallet;

    /**
     * Title of the transaction
     *
     * @var string
     */
    public $title;

    public function __construct(Wallet $wallet, string $title)
    {
        $this->wallet = $wallet;
        $this->title = $title;
    }
}
