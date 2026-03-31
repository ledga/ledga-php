<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum TransactionStatus: string
{
    case Pending = 'pending';
    case Posted = 'posted';
    case Void = 'void';
    case Failed = 'failed';
    case Reversed = 'reversed';
}
