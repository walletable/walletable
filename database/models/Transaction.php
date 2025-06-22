<?php

namespace App\Models;

use Walletable\Models\Transaction as Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'session',
        'confirmed',
        'type',
        'amount',
        'balance',
        'currency',
        'action',
        'remarks',
        'confirmed_at',
        'status'
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
