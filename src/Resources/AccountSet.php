<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;

final readonly class AccountSet implements ResourceInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $id,
        public string $ledgerId,
        public string $code,
        public string $name,
        public ?string $description,
        public ?array $metadata,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'],
            ledgerId: $data['ledger_id'],
            code: $data['code'],
            name: $data['name'],
            description: $data['description'] ?? null,
            metadata: $data['metadata'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }
}
