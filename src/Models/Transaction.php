<?php

namespace Walletable\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Walletable\Traits\ConditionalUuid;

class Transaction extends Model
{
    use HasFactory, ConditionalUuid;
}
