<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum TransactionCodeStatus: string
{
    case Active = 'active';
    case Deprecated = 'deprecated';
}
