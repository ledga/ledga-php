<?php

declare(strict_types=1);

namespace Ledga\Api\Exceptions;

/**
 * Thrown when request validation fails (HTTP 400/422).
 */
class LedgaValidationException extends LedgaException
{
    /**
     * Get validation errors keyed by field name.
     *
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        if ($this->errorBody === null) {
            return [];
        }

        return $this->errorBody['errors'] ?? [];
    }
}
