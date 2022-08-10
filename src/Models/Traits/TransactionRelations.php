<?php

namespace Walletable\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphTo;

trait TransactionRelations
{
    /**
     * Get the wallet this transaction belongs to
     */
    public function wallet()
    {
        return $this->belongsTo(config('walletable.models.wallet'));
    }

    /**
     * Method relationship
     *
     * @return MorphTo
     */
    public function method(): MorphTo
    {
        return $this->morphTo();
    }
}
