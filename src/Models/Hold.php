<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Walletable\Models\Traits\HoldRelations;
use Walletable\Traits\ConditionalUuid;

class Hold extends Model
{
    use HasFactory;
    use ConditionalUuid;
    use HoldRelations;

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


    protected $attributes = [
        'currency' => 'NGN',
        'status' => 'active',
    ];
}
