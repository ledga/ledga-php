<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;
use Ledga\Api\Enums\JournalStatus;

final readonly class Journal implements ResourceInterface
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
        public JournalStatus $status,
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
            status: JournalStatus::from($data['status']),
            metadata: $data['metadata'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }
}
