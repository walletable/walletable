<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locker
    |--------------------------------------------------------------------------
    |
    | Which locking strategy to use when applying postings to wallet balances.
    |
    */
    'locker' => env('WALLETABLE_LOCKER', 'optimistic'),

    /*
    |--------------------------------------------------------------------------
    | Model class names
    |--------------------------------------------------------------------------
    |
    | App-level Eloquent classes that extend the package's base models. Lets
    | you add fillables, casts, factories, and relations without forking the
    | package.
    |
    */
    'models' => [
        'wallet' => \App\Models\Wallet::class,
        'transaction' => \App\Models\Transaction::class,
        'posting' => \App\Models\Posting::class,
        'house_account' => \Walletable\Models\HouseAccount::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Model primary key strategy
    |--------------------------------------------------------------------------
    |
    | 'default' (auto-increment bigint), 'ulid', or 'uuid'. Applies to wallets,
    | transactions, postings, and house accounts uniformly.
    |
    */
    'model_id' => 'default',
];
