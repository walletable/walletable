<?php

namespace App\Models;

use Walletable\Models\Wallet as Model;
use Walletable\Traits\BatchTransactionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use BatchTransactionable;

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
        'walletable_id',
        'walletable_type'
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
