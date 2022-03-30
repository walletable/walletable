<?php

namespace Walletable\Models\Traits;

trait WorkWithMeta
{
    /**
     * Set an item on model data property using dot notation.
     */
    public function meta(string $key = null, $value = null)
    {
        $data = $this->meta;

        if (is_string($key) && !is_null($value)) {
            data_set($data, $key, $value, true);
            $this->forceFill([
                'meta' => $data
            ]);

            return $value;
        } elseif (is_string($key)) {
            return data_get($data, $key, null);
        } else {
            return $data;
        }
    }
}
