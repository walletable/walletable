<?php

namespace Walletable\Models;

interface Walletable
{


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