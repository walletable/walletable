<?php

namespace Walletable\Enums;

class ModelID {
    const ULID = 'ulid';
    const UUID = 'uuid';
    const DEFAULT = 'default';

    public static function values(): array {
        return [
            self::ULID,
            self::UUID,
            self::DEFAULT,
        ];
    }
    
    /**
     * Method from
     *
     * @param string $value 
     *
     * @return string
     */
    public static function from(string $value): string 
    {
        if (in_array($value, self::values(), true)) {
            return $value;
        }

        throw new \InvalidArgumentException("Invalid modelID: $value");
    }
}
