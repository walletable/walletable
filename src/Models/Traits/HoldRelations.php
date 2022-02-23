<?php

namespace Walletable\Models\Traits;

trait HoldRelations
{
    /**
     * Get the wallet this hold belongs to
     */
    public function wallet()
    {
        return $this->hasMany(config('walletable.models.wallet'));
    }
}
