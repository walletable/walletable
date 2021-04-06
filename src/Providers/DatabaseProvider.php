<?php


namespace Walletable\Walletable\Providers;
use Walletable\Walletable\Models\WalletInterface;
use Walletable\Walletable\Models\Walletable;
use Money\Money;
use Money\Currency;

class DatabaseProvider extends ProviderAbstract 
{
    /**
     * This function is used to add funds to a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function creditWallet(int $amount){

        $this->wallet->update(
            [
                'balance' => $this->wallet->balance + $amount,
            ]
        );

    }

    /**
     * This function is used for deducting funds from a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function debitWallet(int $amount){

            $this->wallet->update(
                [
                    'balance' => $this->wallet->balance - $amount,
                ]
            );
    }

    /**
     * This method is used to get the wallet balance
     *  @param bool $withHold This is used to determine if the Holds on the wallet will be calculated
     *  @return mixed
     */
    public function balance(bool $withHold = false){
        return ($withHold) ? $this->balanceFromDB() - $this->holdBalance() : $this->balanceFromDB();
    }

    /**
     * Display a readable amount of the base unit of the wallet currency
     *  @param int $amount The value must be in the base unit of the currency
     *  @return string
     */
    public function display(int $amount):string{
        return display_money(new Money($amount, new Currency($this->wallet->currency)));
    }

    /**
     *  This method is used to get the wallet balance from Database
     *  @return mixed
     */
    public function balanceFromDB(){
        return $this->wallet->balance;
    }

    /**
     *  This method is used to get the wallet balance from the external service the wallet provider is useing
     *  @return mixed
     */
    public function balanceFromService(){
        return $this->balanceFromDB();
    }

    /**
     * This method is used to persist the balance from the externa to the database
     *  
     *  @return bool
     */
    public function persistBalance(){
        return true;
    }

    /**
     *  This method is used to get the providers signature
     *  @return string
     */
    static public function signature() : string{

        return 'database';
    }

    /**
     *  Generate account details from an external service or return an empty array
     *  @return array
     */
    public static function generate( Walletable $owner, WalletInterface $wallet ):array{
        return [
            'success' => true,
            'data' => []
        ];
    }

    /**
     *  Get the name the provider will be addressed with
     *  @return array
     */
    public function providerName():string{
        return 'Database';
    }
}