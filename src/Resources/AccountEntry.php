<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;
use Ledga\Api\Enums\EntryType;
use Ledga\Api\Enums\TransactionLayer;

/**
 * Represents an entry for an account with rolling balance.
 */
final readonly class AccountEntry implements ResourceInterface
{
    public function __construct(
        public string $id,
        public string $transactionId,
        public string $accountId,
        public string $accountCode,
        public EntryType $type,
        public string $amount,
        public ?string $description,
        public TransactionLayer $layer,
        public DateTimeImmutable $effectiveDate,
        public string $balanceAfter,
        public ?TransactionSummary $transaction,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $transaction = null;
        if (isset($data['transaction']) && is_array($data['transaction'])) {
            $transaction = TransactionSummary::fromArray($data['transaction']);
        }

        return new self(
            id: $data['id'],
            transactionId: $data['transaction_id'],
            accountId: $data['account_id'],
            accountCode: $data['account_code'],
            type: EntryType::from($data['type']),
            amount: $data['amount'],
            description: $data['description'] ?? null,
            layer: TransactionLayer::from($data['layer']),
            effectiveDate: new DateTimeImmutable($data['effective_date']),
            balanceAfter: $data['balance_after'] ?? '0.00',
            transaction: $transaction,
        );
    }
}
