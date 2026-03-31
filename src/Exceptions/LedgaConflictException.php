<?php

declare(strict_types=1);

namespace Ledga\Api\Exceptions;

/**
 * Thrown when there is a conflict, such as idempotency key reuse (HTTP 409).
 */
class LedgaConflictException extends LedgaException
{
}
