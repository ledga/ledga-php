<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Resources;

use Ledga\Api\Enums\AccountCategory;
use Ledga\Api\Enums\AccountType;
use Ledga\Api\Enums\NormalBalance;
use Ledga\Api\Resources\Account;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccountTest extends TestCase
{
    #[Test]
    public function it_creates_account_from_array(): void
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'parent_id' => null,
            'category' => 'system',
            'description' => 'Cash account',
            'balance' => '1000.00',
            'is_active' => true,
            'is_system' => false,
            'metadata' => ['key' => 'value'],
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];

        $account = Account::fromArray($data);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $account->id);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174001', $account->ledgerId);
        $this->assertSame('1000', $account->code);
        $this->assertSame('Cash', $account->name);
        $this->assertSame(AccountType::Asset, $account->type);
        $this->assertSame(NormalBalance::Debit, $account->normalBalance);
        $this->assertNull($account->parentId);
        $this->assertSame(AccountCategory::System, $account->category);
        $this->assertSame('Cash account', $account->description);
        $this->assertSame('1000.00', $account->balance);
        $this->assertTrue($account->isActive);
        $this->assertFalse($account->isSystem);
        $this->assertSame(['key' => 'value'], $account->metadata);
    }

    #[Test]
    public function it_handles_optional_fields(): void
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'code' => '1000',
            'name' => 'Cash',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'category' => 'customer',
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];

        $account = Account::fromArray($data);

        $this->assertNull($account->parentId);
        $this->assertSame(AccountCategory::Customer, $account->category);
        $this->assertNull($account->description);
        $this->assertSame('0.00', $account->balance);
        $this->assertTrue($account->isActive);
        $this->assertFalse($account->isSystem);
        $this->assertNull($account->metadata);
    }
}
