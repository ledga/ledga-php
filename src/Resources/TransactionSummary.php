<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

/**
 * A summary of transaction info embedded in account entries.
 */
final readonly class TransactionSummary implements ResourceInterface
{
    public function __construct(
        public ?string $reference,
        public ?string $description,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            reference: $data['reference'] ?? null,
            description: $data['description'] ?? null,
        );
    }
}
