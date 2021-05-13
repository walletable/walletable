<?php


namespace Walletable\Providers;
use Walletable\Models\WalletInterface;
use Illuminate\Database\Eloquent\Model;
use Walletable\Models\Walletable;
use App\Models\WalletHold;
use DB;

abstract class ProviderRepository implements ProviderInterface 
{
    protected $wallet;

    public function __construct( WalletInterface $wallet)
    {
        $this->wallet = $wallet;
    }


    /**
     * This function is used to add funds to a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    abstract public function creditWallet(int $amount);

    /**
     * This function is used for deducting funds from a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    abstract public function debitWallet(int $amount);

    /**
     * This method is used to get the wallet balance
     *  @param bool $withHold This is used to determine if the Holds on the wallet will be calculated
     *  @return mixed
     */
    abstract public function balance(bool $withHold = false);

    /**
     * Display a readable amount of the base unit of the wallet currency
     *  @param int $amount The value must be in the base unit of the currency
     *  @return string
     */
    abstract public function display(int $amount):string;

    /**
     *  This method is used to get the wallet balance from Database
     *  @return mixed
     */
    abstract public function balanceFromDB();

    /**
     *  This method is used to get the wallet balance from the external service the wallet provider is useing
     *  @return mixed
     */
    abstract public function balanceFromService();

    /**
     *  This method is used to get the total amount held to wallet balance from Database
     *  @return mixed
     */
    public function holdBalance(){
        return WalletHold::where('wallet_id', $this->wallet->id)->where('status', 'active')->sum('amount');
    }

    /**
     *  This method is used to get the number of active holds on a wallet
     *  @return mixed
     */
    public function holdCount(){
        return WalletHold::where('wallet_id', $this->wallet->id)->where('status', 'active')->count('id');
    }

    /**
     *  This method is used to get the number of active holds on a wallet
     *  @return mixed
     */
    public function holds(){
        return WalletHold::where('wallet_id', $this->wallet->id)->where('status', 'active')->get();
    }

    /**
     *  This method is used to get the number of active holds on a wallet
     *  @return mixed
     */
    public function scheduledHoldCount(){
        return WalletHold::where('wallet_id', $this->wallet->id)->where('status', 'active')->whereNotNull('relieved_at')->count('id');
    }

    /**
     *  This method is used to get the wallet balance from Database
     *  @return mixed
     */
    public function releaseHold(string $id){
        $hold = WalletHold::where('id', $id)->where('wallet_id', $this->wallet->id)->where('status', 'active')->first([
            'id',
            'wallet_id',
            'status',
        ]);

        if (!$hold) return false;

        $hold->update([
            'status' => 'inactive',
            'action' => 'released',
        ]);

        return true;
    }

    /**
     *  This method is used to get the wallet balance from Database
     *  @return mixed
     */
    public function resolveHold(string $id){
        $hold = WalletHold::where('id', $id)->where('wallet_id', $this->wallet->id)->where('status', 'active')->first([
            'id',
            'wallet_id',
            'amount',
            'status',
        ]);

        if (!$hold) return false;


        DB::beginTransaction();

        try {

            $hold->update([
                'status' => 'inactive',
                'action' => 'resolved',
            ]);

            $this->debit($hold->amount);

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
            
        }

        return true;
    }

    /**
     * This method is used to persist the balance from the externa to the database
     *  
     *  @return bool
     */
    public function persistBalance(){

        $balance = $this->balanceFromService();

        $this->wallet->update(
            [
                'balance' => $balance
            ]
        );

    }

    /**
     *  Used to hold an amount to a wallet balance
     *  @param int $amount The value must be in the base unit of the currency
     *  @param string $label group the hold with a label
     *  @param string $remarks add a remark to descibe the hold
     *  @return mixed
     */
    public function hold(int $amount, string $label, string $remarks){
        return WalletHold::create(
            [
                'wallet_id' => $this->wallet->id,
                'amount' => $amount,
                'label' => $label,
                'remarks' => $remarks,
            ]
        );
    }

    /**
     *  Used to hold an amount to a wallet balance for sometimes
     *  @param int $amount The value must be in the base unit of the currency
     *  @param string $label group the hold with a label
     *  @param string $remarks add a remark to descibe the hold
     *  @return mixed
     */
    public function scheduleHold(int $days, int $amount, string $label, string $remarks){
        return WalletHold::create(
            [
                'wallet_id' => $this->wallet->id,
                'amount' => $amount,
                'label' => $label,
                'relieved_at' => now()->addDays($days),
                'remarks' => $remarks,
            ]
        );
    }

    /**
     *  Used to hold an amount for an entity to a wallet balance
     *  @param Model $for The entity object
     *  @param int $amount The value must be in the base unit of the currency
     *  @param string $label group the hold with a label
     *  @param string $remarks add a remark to descibe the hold
     *  @return mixed
     */
    public function holdFor( Model $for, int $amount, string $label, string $remarks){
        return WalletHold::create(
            [
                'wallet_id' => $this->wallet->id,
                'amount' => $amount,
                'for_id' => $for->{$for->getKeyName()},
                'for_type' => get_class($for),
                'label' => $label,
                'remarks' => $remarks,
            ]
        );
    }

    /**
     *  This method is used to get the providers signature
     *  @return string
     */
    abstract static public function signature() : string;


    /**
     * This method is used to check if a wallet can be debited with a particular amount
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function debitableAmount(int $amount):bool{
        return ($this->balance() < $amount )?  true : false;
    }

    
    /**
     *  This method is used to check if a wallet can be credited with a particular amount
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function creditableAmount(int $amount):bool{
        return true;
    }


    /**
     * This function is used to add funds to a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function credit(int $amount){

        DB::beginTransaction();


        try {

            if (!$this->creditableAmount($amount)) throw new \Exception('Invalid Transaction');

            $this->creditWallet($amount);

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
            
        }
    }


    /**
     * This function is used for deducting funds from a wallet
     *  @param int $amount The value must be in the base unit of the currency
     *  @return bool
     */
    public function debit(int $amount){

        DB::beginTransaction();


        try {

            if ($this->debitableAmount($amount)) throw new \Exception('Insufficient balance');

            $this->debitWallet($amount);

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;

        }
        
      /*   if ( !($this->wallet->balance >= $amount) ){
            $this->wallet->update(
                [
                    'balance' => $this->wallet->balance - $amount,
                ]
            );
        }else{
            $hold = $amount - $this->wallet->balance;

            $this->wallet->update(
                [
                    'balance' => 0,
                ]
            );

            $this->hold($hold, 'overdraft', '');
        } */

    }

    /**
     *  Generate account details from an external service or return an empty array
     *  @return array
     */
    abstract public static function generate( Walletable $owner, WalletInterface $wallet ):array;

    /**
     *  Get the name the provider will be addressed with
     *  @return array
     */
    abstract public function providerName():string;

    
    /**
     *  Get the wallet database model
     *  @return WalletInterface
     */
    public function model() : WalletInterface{
        return $this->wallet;
    }

}