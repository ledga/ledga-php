<?php

declare(strict_types=1);

namespace Ledga\Api\Exceptions;

use Exception;
use Throwable;

class LedgaException extends Exception
{
    /**
     * @param array<string, mixed>|null $errorBody
     */
    public function __construct(
        string $message,
        public readonly ?array $errorBody = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
