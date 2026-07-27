<?php

namespace Walletable\Traits;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

trait ConditionalID
{
    /**
     * Boot function from laravel.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model_id = config('walletable.model_id');

            if ($model_id !== 'default' && empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = $model_id === 'uuid'
                    ? (string) Str::orderedUuid()
                    : strtolower((string) Str::ulid());
            }
        });
    }

    /**
     * Foreign keys referencing walletable's own tables, which follow the same
     * key strategy as the primary keys.
     *
     * @return array<int, string>
     */
    protected function walletableKeys(): array
    {
        return [];
    }

    /**
     * Cast the walletable foreign keys to the configured key type. Uncast, their
     * PHP type is decided by the driver: PHP 8.0 and emulated prepares return
     * strings where later versions return integers.
     */
    public function initializeConditionalID(): void
    {
        $this->mergeCasts(
            array_fill_keys($this->walletableKeys(), $this->getKeyType())
        );
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     *
     * @return bool
     */
    public function getIncrementing(): bool
    {
        return config('walletable.model_id') === 'default';
    }

    /**
     * Get the auto-incrementing key type.
     *
     * @return string
     */
    public function getKeyType()
    {
        if (config('walletable.model_id') !== 'default') {
            return 'string';
        }

        return $this->keyType;
    }

    /**
     * Retrieve the model for a bound value.
     *
     * @param  \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\Relation  $query
     * @param  mixed  $value
     * @param  string|null  $field
     * @return \Illuminate\Contracts\Database\Eloquent\Builder
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function resolveRouteBindingQuery($query, $value, $field = null)
    {
        $model_id = config('walletable.model_id');

        if ($model_id !== 'default') {

            $value_is = ($model_id === 'uuid') ? Str::isUuid($value) : Str::isUlid($value);

            if ($field && in_array($field, $this->uniqueIds()) && ! $value_is) {
                throw (new ModelNotFoundException)->setModel(get_class($this), $value);
            }
    
            if (! $field && in_array($this->getRouteKeyName(), $this->uniqueIds()) && ! $value_is) {
                throw (new ModelNotFoundException)->setModel(get_class($this), $value);
            }
        }

        return parent::resolveRouteBindingQuery($query, $value, $field);
    }
}
