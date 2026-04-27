<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use DateTimeImmutable;
use Ledga\Api\Enums\AccountType;
use Ledga\Api\Enums\NormalBalance;

final readonly class AccountBalance implements ResourceInterface
{
    /**
     * @param array<string, mixed> $layerDetails
     */
    public function __construct(
        public string $accountId,
        public string $accountCode,
        public string $accountName,
        public AccountType $accountType,
        public NormalBalance $normalBalance,
        public string $settled,
        public string $pending,
        public string $overdue,
        public string $future,
        public array $layerDetails,
        public string $currency,
        public DateTimeImmutable $asOfDate,
        public DateTimeImmutable $calculatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        /** @var array<string, mixed> $payload */
        $payload = $data['data'] ?? $data;

        /** @var array<string, mixed> $balances */
        $balances = $payload['balances'];

        /** @var array<string, mixed> $layerDetails */
        $layerDetails = $payload['layer_details'];

        return new self(
            accountId: (string) $payload['account_id'],
            accountCode: (string) $payload['account_code'],
            accountName: (string) $payload['account_name'],
            accountType: AccountType::from((string) $payload['account_type']),
            normalBalance: NormalBalance::from((string) $payload['normal_balance']),
            settled: (string) $balances['settled'],
            pending: (string) $balances['pending'],
            overdue: (string) $balances['overdue'],
            future: (string) $balances['future'],
            layerDetails: $layerDetails,
            currency: (string) $payload['currency'],
            asOfDate: new DateTimeImmutable((string) $payload['as_of_date']),
            calculatedAt: new DateTimeImmutable((string) $payload['calculated_at']),
        );
    }
}
