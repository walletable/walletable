<?php

namespace ManeOlawale\Walletable\Providers;

use ManeOlawale\Walletable\Models\WalletInterface;
use Illuminate\Database\Eloquent\Model;
use ManeOlawale\Walletable\Models\Walletable;

interface ProviderInterface 
{
    /**
     * This function is used to add funds to a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function creditWallet(int $amount);

    /**
     * This function is used for deducting funds from a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function debitWallet(int $amount);

    /**
     * This method is used to get the wallet balance
     *  @param bool $withHold This is used to determine if the Holds on the wallet will be calculated
     *  @return mixed
     */
    public function balance(bool $withHold = false);

    /**
     * Display a readable amount of the base unit of the wallet currency
     *  @param int $amount The value must be in the base unit of the currency
     *  @return string
     */
    public function display(int $amount):string;

    /**
     *  This method is used to get the wallet balance from Database
     *  @return mixed
     */
    public function balanceFromDB();

    /**
     *  This method is used to get the wallet balance from the external service the wallet provider is useing
     *  @return mixed
     */
    public function balanceFromService();

    /**
     *  This method is used to get the total amount held to wallet balance from Database
     *  @return mixed
     */
    public function holdBalance();

    /**
     *  This method is used to get the number of active holds on a wallet
     *  @return mixed
     */
    public function holdCount();

    /**
     *  This method is used to release a hold on a wallet
     *  @return mixed
     */
    public function releaseHold(string $id);

    /**
     * This method is used to persist the balance from the externa to the database
     *  
     *  @return bool
     */
    public function persistBalance();

    /**
     *  Used to hold an amount to a wallet balance
     *  @param int $amount The value must be in the base unit of the currency
     *  @param string $label group the hold with a label
     *  @param string $remarks add a remark to descibe the hold
     *  @return mixed
     */
    public function hold(int $amount, string $label, string $remarks);

    /**
     *  Used to hold an amount for an entity to a wallet balance
     *  @param Model $for The entity object
     *  @param int $amount The value must be in the base unit of the currency
     *  @param string $label group the hold with a label
     *  @param string $remarks add a remark to descibe the hold
     *  @return mixed
     */
    public function holdFor( Model $for, int $amount, string $label, string $remarks);

    /**
     *  This method is used to get the providers signature
     *  @return string
     */
    static public function signature() : string;

    /**
     *  This method is used to check if a wallet can be debited with a particular amount
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function debitableAmount(int $amount):bool;

    
    /**
     *  This method is used to check if a wallet can be credited with a particular amount
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function creditableAmount(int $amount):bool;

    /**
     *  Generate account details from an external service or return an empty array
     *  @return array
     */
    public static function generate( Walletable $owner, WalletInterface $wallet ):array;

    /**
     *  Get the name the provider will be addressed with
     *  @return array
     */
    public function providerName():string;
}