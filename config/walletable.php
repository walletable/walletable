<?php


return [

    /*
    |--------------------------------------------------------------------------
    | Default Wallet Driver Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the wallet drivers you wish to use
    | as your default driver for all wallet created incase you didn`t
    | specify any driver during creation. Of course you may use many 
    | drivers at once using the Database library.
    |
    */

    'default' => env('WALLETABLE_DRIVER', 'database'),


    'models' => [
        'wallet' => ManeOlawale\Walletable\Models\Wallet::class,
    ],

];