<?php

namespace App\Models;

use Walletable\Models\Hold as Model;

class Hold extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'amount',
        'for_id',
        'for_type',
        'currency',
        'label',
        'remarks',
        'action',
        'status',
        'relieved_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array'
    ];
}
