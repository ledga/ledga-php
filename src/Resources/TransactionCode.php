<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;

final readonly class TransactionCode implements ResourceInterface
{
    /**
     * @param array<string, mixed>|null $paramsSchema
     * @param array<string, mixed> $entriesTemplate
     * @param array<string, mixed>|null $validationRules
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $id,
        public string $ledgerId,
        public string $code,
        public string $name,
        public ?string $description,
        public string $status,
        public int $version,
        public ?array $paramsSchema,
        public array $entriesTemplate,
        public ?array $validationRules,
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
            status: $data['status'] ?? 'active',
            version: $data['version'] ?? 1,
            paramsSchema: $data['params_schema'] ?? null,
            entriesTemplate: $data['entries_template'] ?? [],
            validationRules: $data['validation_rules'] ?? null,
            metadata: $data['metadata'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }
}
