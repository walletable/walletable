<?php

namespace Walletable\Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WalletHold extends Model
{
    use HasFactory, Traits\Uuids;

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
        'label',
        'remarks',
        'relieved_at',
        'action',
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


    protected $attributes = [
        'currency' => 'NGN',
        'status' => 'active',
    ];
}
