<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;
use Ledga\Api\Enums\AccountCategory;
use Ledga\Api\Enums\AccountType;
use Ledga\Api\Enums\NormalBalance;

final readonly class Account implements ResourceInterface
{
    /**
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $id,
        public string $ledgerId,
        public string $code,
        public string $name,
        public AccountType $type,
        public NormalBalance $normalBalance,
        public AccountCategory $category,
        public ?string $parentId,
        public ?string $description,
        public string $balance,
        public bool $isActive,
        public bool $isSystem,
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
            type: AccountType::from($data['type']),
            normalBalance: NormalBalance::from($data['normal_balance']),
            category: AccountCategory::from($data['category']),
            parentId: $data['parent_id'] ?? null,
            description: $data['description'] ?? null,
            balance: $data['balance'] ?? '0.00',
            isActive: $data['is_active'] ?? true,
            isSystem: $data['is_system'] ?? false,
            metadata: $data['metadata'] ?? null,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }
}
