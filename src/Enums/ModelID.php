<?php

namespace Walletable\Enums;

enum ModelID: string
{
    case ULID = 'ulid';
    case UUID = 'uuid';
    case DEFAULT = 'default';
}