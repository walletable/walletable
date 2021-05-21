<?php

namespace Walletable\Traits;


use Illuminate\Support\Str;


trait ConditionalUuid
{

    /**
     * Boot function from laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (config('walletable.uuid.'.$this->getTable()) && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Str::Uuid();
            }
        });
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing()
    {
        return false;
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        return 'string';
    }
}