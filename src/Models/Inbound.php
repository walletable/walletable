<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
//use Walletable\Traits\ConditionalUuid;

class Inbound extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'reference',
        'currency',
        'label',
        'identifier',
        'service_name',
        'service_id',
        'status',
        'data',
        'driver',
    ];

    protected $attributes = [
        'status' => 'active',
    ];
}
