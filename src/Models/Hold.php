<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Walletable\Traits\ConditionalUuid;

class Hold extends Model
{
    use HasFactory, ConditionalUuid;

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

/*     /**
     * The attributes that should be cast to native types.
     *
     * @var array
     *
    protected $casts = [
        'status' => \App\Casts\Status::class,
    ]; */


    protected $attributes = [
        'currency' => 'NGN',
        'status' => 'active',
    ];
}
