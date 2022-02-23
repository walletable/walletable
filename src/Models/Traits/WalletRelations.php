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
     * Get holds of wallet
     */
    public function holds()
    {
        return $this->hasMany(config('walletable.models.hold'));
    }

    /**
     * Get the wallet owner
     */
    public function walletable()
    {
        return $this->morphTo();
    }
}
