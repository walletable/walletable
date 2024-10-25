<?php

return [

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
        'transaction' => \App\Models\Transaction::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model uuid primary keys
    |--------------------------------------------------------------------------
    |
    | By default, Walletable uses auto-incrementing primary keys when assigning
    | IDs to clients. However, when installing Walletable you will be asked
    | to choose which model ID to use. Accepted values are 'default', 'uuid' and 'ulid'
    | If you want to auto-increment leave it at default
    |
    */
    'model_id' => 'default',

    /*
    |--------------------------------------------------------------------------
    | UUID Generation Driver
    |--------------------------------------------------------------------------
    |
    | By default, Walletable use Illuminate\Support\Str::orderedUuid() to generate
    | "ordered" UUIDs for walletable models If you choose to use 'uuid'. These UUIDs 
    | are more efficient for indexed database storage because they can be sorted 
    | lexicographically. You can override this behaviour by defining a new driver here. 
    |
    */
    'uuid_driver' => '\Illuminate\Support\Str::orderedUuid()',
];
