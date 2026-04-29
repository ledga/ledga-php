<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Resources;

use Ledga\Api\Enums\EntryType;
use Ledga\Api\Enums\TransactionLayer;
use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Resources\Transaction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransactionTest extends TestCase
{
    #[Test]
    public function it_creates_transaction_from_array(): void
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'journal_id' => '123e4567-e89b-12d3-a456-426614174002',
            'reference' => 'INV-001',
            'description' => 'Payment received',
            'effective_date' => '2025-01-01T12:00:00Z',
            'layer' => 'settled',
            'status' => 'posted',
            'total_amount' => '100.00',
            'entry_count' => 2,
            'hash' => 'abc123',
            'previous_hash' => 'xyz789',
            'correlation_id' => 'corr-001',
            'metadata' => ['invoice_id' => '123'],
            'original_transaction_id' => null,
            'reversal_reason' => null,
            'entries' => [
                [
                    'id' => 'entry-1',
                    'account_id' => 'acc-1',
                    'account_code' => '1000',
                    'account_name' => 'Cash',
                    'amount' => '100.00',
                    'type' => 'debit',
                    'description' => null,
                    'layer' => 'settled',
                ],
                [
                    'id' => 'entry-2',
                    'account_id' => 'acc-2',
                    'account_code' => '4000',
                    'account_name' => 'Revenue',
                    'amount' => '100.00',
                    'type' => 'credit',
                    'description' => null,
                    'layer' => 'settled',
                ],
            ],
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];

        $transaction = Transaction::fromArray($data);

        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $transaction->id);
        $this->assertSame('Payment received', $transaction->description);
        $this->assertSame(TransactionLayer::Settled, $transaction->layer);
        $this->assertSame(TransactionStatus::Posted, $transaction->status);
        $this->assertSame('100.00', $transaction->totalAmount);
        $this->assertCount(2, $transaction->entries);
        $this->assertSame(EntryType::Debit, $transaction->entries[0]->type);
        $this->assertSame(EntryType::Credit, $transaction->entries[1]->type);
    }

    #[Test]
    public function it_handles_date_field_alias(): void
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'description' => 'Test',
            'date' => '2025-01-01T12:00:00Z',
            'status' => 'pending',
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];

        $transaction = Transaction::fromArray($data);

        $this->assertSame('2025-01-01', $transaction->effectiveDate->format('Y-m-d'));
    }
}
