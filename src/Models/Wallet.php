<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Walletable\Contracts\WalletInterface;
use Walletable\Models\Relations\HasTransactions;
use Walletable\Models\Relations\WalletRelations;
use Walletable\Traits\ConditionalUuid;

class Wallet extends Model implements WalletInterface
{
    use HasFactory;
    use ConditionalUuid;
    use WalletRelations;

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
        'data',
        'driver',
        'status',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        //
    ];
}