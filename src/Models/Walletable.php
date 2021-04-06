<?php

namespace Walletable\Walletable\Models;

interface Walletable
{
    /**
     * This method provides the account name of the wallet as specified by the wallet
     * @return string
     */
    public function account_name():string;


    /**
     * Generate a wallet for the model
     * @return string
     */
    public function createWallet(string $provider = null);


    /**
     * Generate a wallet for the model
     * @return string
     */
    public function getWallet(string $provider = null);

}