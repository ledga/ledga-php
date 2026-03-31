<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum JournalStatus: string
{
    case Active = 'active';
    case Closed = 'closed';
}
