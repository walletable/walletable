<?php

namespace Walletable\Tests\Models;

use Walletable\Models\Wallet as Model;

class Wallet extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'label',
        'tag',
        'amount',
        'currency',
        'meta',
        'driver',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array'
    ];
}
