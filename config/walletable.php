<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Wallet Driver
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the wallet drivers you wish to use
    | as your default driver for all wallet created incase you didn`t
    | specify any driver during creation.
    |
    */
    'default' => env('WALLETABLE_DRIVER', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Locker
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the locking mechanism to us when altering
    | wallet balance to avoid race condition
    |
    */
    'locker' => env('WALLETABLE_LOCKER', 'optimistic'),

    /*
    |--------------------------------------------------------------------------
    | Model class names
    |--------------------------------------------------------------------------
    |
    | You can set model class names here, so walletable and other
    | related packages can use the correct class names
    |
    */
    'models' => [
        'wallet' => \App\Models\Wallet::class,
        'hold' => \App\Models\Hold::class,
        'inbound' => \App\Models\Inbound::class,
        'outbound' => \App\Models\Outbound::class,
        'transaction' => \App\Models\Transaction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model uuid primary keys
    |--------------------------------------------------------------------------
    |
    | By default, Walletable uses auto-incrementing primary keys when assigning
    | IDs to clients. However, if Walletable is instaalling you will be asked
    | to choose if you want to use uuid instead, this will be set to "true" and
    | UUIDs will be used.
    |
    */
    'model_uuids' => false,

];
