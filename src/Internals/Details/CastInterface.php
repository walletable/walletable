<?php

namespace Walletable\Internals\Details;

interface CastInterface
{
    /**
     * Represent the data in string
     *
     * @param mixed $value
     */
    public function string(Info $info, $value): string;

    /**
     * Represent the data in string
     *
     * @param mixed $value
     */
    public function json(Info $info, $value);
}
