<?php

namespace Walletable\Models\Traits;

trait WorkWithData
{
    /**
     * Set an item on model data property using dot notation.
     */
    public function data(string $key = null, array $value = null)
    {
        $data = $this->data;

        if (is_string($key) && !is_null($value)) {
            data_set($data, $key, $value, true);
            $this->forceFill([
                'data' => $data
            ]);

            return $value;
        } elseif (is_string($key)) {
            return data_get($data, $key, null);
        } else {
            return $data;
        }
    }
}
