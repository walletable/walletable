<?php

namespace Walletable\Models\Traits;

trait TransactionRelations
{
    /**
     * Get the wallet this transaction belongs to
     */
    public function wallet()
    {
        return $this->hasMany(config('walletable.models.wallet'));
    }

    /**
     * Get mothod entity of this transaction
     */
    public function method()
    {
        return $this->morphTo();
    }
}
