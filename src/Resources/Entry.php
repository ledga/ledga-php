<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use Ledga\Api\Enums\EntryType;
use Ledga\Api\Enums\TransactionLayer;

final readonly class Entry implements ResourceInterface
{
    public function __construct(
        public string $id,
        public string $accountId,
        public string $accountCode,
        public ?string $accountName,
        public string $amount,
        public EntryType $type,
        public ?string $description,
        public TransactionLayer $layer,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'],
            accountId: $data['account_id'],
            accountCode: $data['account_code'],
            accountName: $data['account_name'] ?? null,
            amount: $data['amount'],
            type: EntryType::from($data['type']),
            description: $data['description'] ?? null,
            layer: TransactionLayer::from($data['layer'] ?? 'settled'),
        );
    }
}
