<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Resources;

use Ledga\Api\Enums\AccountType;
use Ledga\Api\Enums\NormalBalance;
use Ledga\Api\Resources\AccountBalance;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ValueError;

final class AccountBalanceTest extends TestCase
{
    #[Test]
    public function it_parses_the_enveloped_response(): void
    {
        $balance = AccountBalance::fromArray($this->envelope());

        $this->assertSame('123', $balance->accountId);
        $this->assertSame(AccountType::Asset, $balance->accountType);
        $this->assertSame(NormalBalance::Debit, $balance->normalBalance);
        $this->assertSame('1000.00', $balance->settled);
        $this->assertSame('20.00', $balance->overdue);
        $this->assertSame('80.00', $balance->future);
        $this->assertSame('GBP', $balance->currency);
    }

    #[Test]
    public function it_fails_loudly_on_unknown_account_type(): void
    {
        $payload = $this->envelope();
        $payload['data']['account_type'] = 'gibberish';

        $this->expectException(ValueError::class);

        AccountBalance::fromArray($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function envelope(): array
    {
        return [
            'success' => true,
            'data' => [
                'account_id' => '123',
                'account_code' => '1000',
                'account_name' => 'Cash',
                'account_type' => 'asset',
                'normal_balance' => 'debit',
                'balances' => [
                    'settled' => '1000.00',
                    'pending' => '50.00',
                    'overdue' => '20.00',
                    'future' => '80.00',
                ],
                'layer_details' => [],
                'currency' => 'GBP',
                'as_of_date' => '2025-01-15',
                'calculated_at' => '2025-01-15T12:00:00Z',
            ],
        ];
    }
}
