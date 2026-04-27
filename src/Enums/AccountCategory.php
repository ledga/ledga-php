<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum AccountCategory: string
{
    case System = 'system';
    case Customer = 'customer';
}
