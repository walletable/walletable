<?php

namespace Walletable\Internals\Details;

class TextCast implements CastInterface
{
    public function string(Info $info, $value): string
    {
        return (string)$value;
    }

    public function json(Info $info, $value)
    {
        return (string)$value;
    }
}
