<?php

namespace ManeOlawale\Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model implements WalletInterface
{
    use HasFactory, Traits\Uuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'walletable_id',
        'walletable_type',
        'name',
        'label',
        'amount',
        'currency_id',
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
