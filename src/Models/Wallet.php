<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Walletable\Traits\ConditionalUuid;

class Wallet extends Model implements WalletInterface
{
    use HasFactory, ConditionalUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'walletable_id',
        'walletable_type',
        'label',
        'tag',
        'amount',
        'currency',
        'data',
        'provider',
        'status',
    ];

/*     /**
     * The attributes that should be cast to native types.
     *
     * @var array
     *
    protected $casts = [
        'status' => \App\Casts\Status::class,
    ]; */
}
