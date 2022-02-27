<?php

namespace Walletable\Models\Traits;

trait WalletRelations
{
    /**
     * Get transactions of wallet
     */
    public function transactions()
    {
        return $this->hasMany(config('walletable.models.transaction'));
    }

    /**
     * Get the wallet owner
     */
    public function walletable()
    {
        return $this->morphTo();
    }
}
