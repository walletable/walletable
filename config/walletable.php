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
    | Model primary key strategy
    |--------------------------------------------------------------------------
    |
    | Controls how primary keys are generated for Walletable models.
    | Accepted values: 'default' (auto-incrementing bigint), 'ulid', 'uuid'.
    | ULID and UUID are opt-in alternatives — compact, lexicographically
    | sortable, and useful when IDs are exposed in URLs or webhooks.
    |
    */
    'model_id' => 'default',
];
