<?php

declare(strict_types=1);

namespace Ledga\Api\Exceptions;

use Throwable;

/**
 * Thrown when rate limits are exceeded (HTTP 429).
 */
class LedgaRateLimitException extends LedgaException
{
    /**
     * @param array<string, mixed>|null $errorBody
     */
    public function __construct(
        string $message,
        ?array $errorBody = null,
        public readonly ?int $retryAfter = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $errorBody, $previous);
    }

    /**
     * Get the number of seconds to wait before retrying.
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }
}
