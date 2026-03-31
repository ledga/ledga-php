<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum TransactionLayer: string
{
    case Settled = 'SETTLED';
    case Pending = 'PENDING';
    case Encumbrance = 'ENCUMBRANCE';
}
