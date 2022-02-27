<?php

namespace Walletable\Internals\Details;

use Walletable\Money\Money;

class MoneyCast implements CastInterface
{
    public function string(Info $info, $value): string
    {
        return (string)(new Money($value, $info->extra('currency')));
    }

    public function json(Info $info, $value)
    {
        return (string)(new Money($value, $info->extra('currency')))->json();
    }
}
