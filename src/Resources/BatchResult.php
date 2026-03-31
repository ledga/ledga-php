<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

/**
 * Represents a single result from a batch transaction request.
 */
final readonly class BatchResult implements ResourceInterface
{
    public function __construct(
        public string $idempotencyKey,
        public string $status,
        public ?string $id,
        public ?string $correlationId,
        public ?string $error,
        public ?string $errorCode,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            idempotencyKey: $data['idempotency_key'],
            status: $data['status'],
            id: $data['id'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            error: $data['error'] ?? null,
            errorCode: $data['error_code'] ?? null,
        );
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
