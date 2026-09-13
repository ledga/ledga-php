<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

/**
 * Aggregate balance across every account in a set (nested sets included).
 */
final readonly class AccountSetBalance implements ResourceInterface
{
    public function __construct(
        public string $accountSetId,
        public string $accountSetCode,
        public string $accountSetName,
        public string $totalBalance,
        public string $currency,
        public int $accountCount,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            accountSetId: $data['account_set_id'],
            accountSetCode: $data['account_set_code'],
            accountSetName: $data['account_set_name'],
            totalBalance: $data['total_balance'],
            currency: $data['currency'],
            accountCount: $data['account_count'],
        );
    }
}
