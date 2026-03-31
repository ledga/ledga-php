<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum EntryType: string
{
    case Debit = 'debit';
    case Credit = 'credit';
}
