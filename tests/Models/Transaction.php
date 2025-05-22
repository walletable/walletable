<?php

namespace Walletable\Tests\Models;

use Walletable\Models\Transaction as Model;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        //
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'array',
        'confirmed' => 'boolean',
        'confirmed_at' => 'datetime',
        'created_at' => 'datetime',
    ];
}
