<?php

namespace Walletable\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Walletable\Contracts\Walletable as ContractsWalletable;

class Walletable extends Model implements ContractsWalletable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email'
    ];

    /**
     * Get the name of wallet owner
     *
     * @return string
     */
    public function getOwnerName()
    {
        return $this->name;
    }

    /**
     * Get the email of wallet owner
     *
     * @return string
     */
    public function getOwnerEmail()
    {
        return $this->email;
    }

    /**
     * Get the ID of owner
     *
     * @return string
     */
    public function getOwnerID()
    {
        return $this->getKey();
    }

    /**
     * Get the ID of owner
     *
     * @return string
     */
    public function getOwnerImage()
    {
        return '/avatar.jpg';
    }

    /**
     * Get the morph name of owner
     *
     * @return string
     */
    public function getOwnerMorphName()
    {
        return $this->getMorphClass();
    }

    public function wallets(): MorphMany
    {
        return $this->MorphMany(Wallet::class, 'walletable');
    }
}
