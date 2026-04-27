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
        $payload = $data['data'];

        /** @var array<string, mixed> $balances */
        $balances = $payload['balances'];

        /** @var array<string, mixed> $layerDetails */
        $layerDetails = $payload['layer_details'];

        return new self(
            accountId: $payload['account_id'],
            accountCode: $payload['account_code'],
            accountName: $payload['account_name'],
            accountType: AccountType::from($payload['account_type']),
            normalBalance: NormalBalance::from($payload['normal_balance']),
            settled: $balances['settled'],
            pending: $balances['pending'],
            overdue: $balances['overdue'],
            future: $balances['future'],
            layerDetails: $layerDetails,
            currency: $payload['currency'],
            asOfDate: new DateTimeImmutable($payload['as_of_date']),
            calculatedAt: new DateTimeImmutable($payload['calculated_at']),
        );
    }
}
