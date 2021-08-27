<?php


namespace Walletable\Providers;
use Walletable\Models\WalletInterface;
use Walletable\Models\Walletable;
use Walletable\Wallet;
//use Walletable\Exceptions\WalletGenerationException;
use Money\Money;
use Money\Currency;

class DatabaseProvider extends ProviderRepository 
{
    /**
     * This function is used to add funds to a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function credit(int $amount){

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
    public function debit(int $amount){

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
    public function signature() : string{

        return 'database';
    }

    /**
     *  Generate account details from an external service or return an empty array
     *  @return array
     */
    public function generate( Walletable $owner, WalletInterface $wallet ):array
    {
        return [
            'data' => []
        ];
    }

    /**
     *  Check compatibility between the wallet and the other wallet that is trying to transact with it
     *  - This is used in making handshakes between wallets
     * 
     *  @param Wallet $mainWallet
     *  @param Wallet $otherWallet
     *  @return boolean
     */
    public function compatible( Wallet $mainWallet,  Wallet $otherWallet ):boolean
    {
        return ($this->otherWallet->provider === $this->signature()) || ($this->mainWallet->currency === $this->otherWallet->currency);
        
    }

    /**
     *  Get the currency profiles of supported currencies
     * 
     *  @return array
     */
    public function currencies(): array
    {
        return [
            'NGN' => [
                'code' => 'NGN',
                'symbol' => '₦',
                'name' => 'Naira',
                'subunit' => 'Kobo',
                'per' => 100,
                'precision' => 2,
                'number' => 566,
                'unit_separator' => '.',
                'thousand_separator' => ',',
            ],
            'USD' => [
                'code' => 'USD',
                'symbol' => '$',
                'name' => 'Dollar',
                'subunit' => 'Cent',
                'per' => 100,
                'precision' => 2,
                'number' => 840,
                'unit_separator' => '.',
                'thousand_separator' => ',',
            ],
            'GBP' => [
                'code' => 'GBP',
                'symbol' => '£',
                'name' => 'Pound Sterling',
                'subunit' => 'Pence',
                'per' => 100,
                'precision' => 2,
                'number' => 826,
                'unit_separator' => '.',
                'thousand_separator' => ',',
            ],
            'CAD' => [
                'code' => 'CAD',
                'symbol' => '¢',
                'name' => 'Pound Sterling',
                'subunit' => 'Pence',
                'per' => 100,
                'precision' => 2,
                'number' => 124,
                'unit_separator' => '.',
                'thousand_separator' => ',',
            ],
        ];
    }

    /**
     *  Get the name the provider will be addressed with
     *  @return array
     */
    public function providerName() :string {
        return 'Database';
    }
}