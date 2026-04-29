<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;
use Ledga\Api\Enums\TransactionLayer;
use Ledga\Api\Enums\TransactionStatus;

final readonly class Transaction implements ResourceInterface
{
    /**
     * @param Entry[] $entries
     * @param array<string, mixed>|null $metadata
     */
    public function __construct(
        public string $id,
        public string $ledgerId,
        public ?string $journalId,
        public ?string $reference,
        public string $description,
        public DateTimeImmutable $effectiveDate,
        public TransactionLayer $layer,
        public TransactionStatus $status,
        public string $totalAmount,
        public int $entryCount,
        public ?string $hash,
        public ?string $previousHash,
        public ?string $correlationId,
        public ?array $metadata,
        public ?string $originalTransactionId,
        public ?string $reversalReason,
        public array $entries,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $entries = [];
        if (isset($data['entries']) && is_array($data['entries'])) {
            foreach ($data['entries'] as $entry) {
                $entries[] = Entry::fromArray($entry);
            }
        }

        return new self(
            id: $data['id'],
            ledgerId: $data['ledger_id'],
            journalId: $data['journal_id'] ?? null,
            reference: $data['reference'] ?? null,
            description: $data['description'],
            effectiveDate: new DateTimeImmutable($data['effective_date'] ?? $data['date']),
            layer: TransactionLayer::from($data['layer'] ?? 'settled'),
            status: TransactionStatus::from($data['status']),
            totalAmount: $data['total_amount'] ?? '0.00',
            entryCount: $data['entry_count'] ?? count($entries),
            hash: $data['hash'] ?? null,
            previousHash: $data['previous_hash'] ?? null,
            correlationId: $data['correlation_id'] ?? null,
            metadata: $data['metadata'] ?? null,
            originalTransactionId: $data['original_transaction_id'] ?? null,
            reversalReason: $data['reversal_reason'] ?? null,
            entries: $entries,
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
        );
    }
}
