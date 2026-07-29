<?php

namespace Walletable\Tests\Models;

use Walletable\Models\Wallet as Model;

class Wallet extends Model
{
    protected $fillable = [
        'label',
        'tag',
        'amount',
        'currency',
        'meta',
        'status',
        'walletable_id',
        'walletable_type',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}
