<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

/**
 * Represents account balance history data.
 */
final readonly class BalanceHistory implements ResourceInterface
{
    /**
     * @param array<string, mixed> $account
     * @param array<string, mixed> $period
     * @param array<int, array<string, mixed>> $history
     */
    public function __construct(
        public array $account,
        public array $period,
        public array $history,
        public string $endingBalance,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            account: $data['account'] ?? [],
            period: $data['period'] ?? [],
            history: $data['history'] ?? [],
            endingBalance: $data['ending_balance'] ?? '0.00',
        );
    }
}
