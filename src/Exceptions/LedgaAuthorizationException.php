<?php

declare(strict_types=1);

namespace Ledga\Api\Exceptions;

/**
 * Thrown when the API key lacks required permissions (HTTP 403).
 */
class LedgaAuthorizationException extends LedgaException
{
}
