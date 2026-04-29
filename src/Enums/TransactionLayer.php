<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum TransactionLayer: string
{
    case Settled = 'settled';
    case Pending = 'pending';
    case Encumbrance = 'encumbrance';
}
