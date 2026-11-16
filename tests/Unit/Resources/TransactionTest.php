<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Resources;

use Ledga\Api\Enums\EntryType;
use Ledga\Api\Enums\TransactionLayer;
use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Resources\Transaction;
use PHPUnit\Framework\Attributes\DataProvider;
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

    /**
     * @return array<string, array{0: string, 1: TransactionStatus}>
     */
    public static function statusProvider(): array
    {
        // Every value core's Transaction.status enum holds.
        return [
            'posted' => ['posted', TransactionStatus::Posted],
            'reversed' => ['reversed', TransactionStatus::Reversed],
            'void' => ['void', TransactionStatus::Void],
            'draft' => ['draft', TransactionStatus::Draft],
            'pending' => ['pending', TransactionStatus::Pending],
        ];
    }

    #[Test]
    #[DataProvider('statusProvider')]
    public function it_parses_every_status_the_api_returns(string $wire, TransactionStatus $expected): void
    {
        $transaction = Transaction::fromArray(array_merge($this->minimalData(), ['status' => $wire]));

        $this->assertSame($expected, $transaction->status);
    }

    #[Test]
    public function it_reads_correlation_link_and_void_fields(): void
    {
        $transaction = Transaction::fromArray(array_merge($this->minimalData(), [
            'status' => 'void',
            'correlation_id' => 'SCHED-2026',
            'correlation_source_id' => '018f0000-0000-7000-8000-0000000000a1',
            'correlation_type' => 'reschedule',
            'voided_by' => ['type' => 'api_key', 'id' => '9a0e8400-e29b-41d4-a716-446655440009'],
            'voided_at' => '2026-06-19T10:00:00+00:00',
            'void_reason' => 'rescheduled',
        ]));

        $this->assertSame(TransactionStatus::Void, $transaction->status);
        $this->assertSame('SCHED-2026', $transaction->correlationId);
        $this->assertSame('018f0000-0000-7000-8000-0000000000a1', $transaction->correlationSourceId);
        $this->assertSame('reschedule', $transaction->correlationType);
        $this->assertSame(
            ['type' => 'api_key', 'id' => '9a0e8400-e29b-41d4-a716-446655440009'],
            $transaction->voidedBy,
        );
        $this->assertNotNull($transaction->voidedAt);
        $this->assertSame('2026-06-19T10:00:00+00:00', $transaction->voidedAt->format(DATE_ATOM));
        $this->assertSame('rescheduled', $transaction->voidReason);
    }

    #[Test]
    public function it_reads_null_link_and_void_fields_as_null(): void
    {
        $transaction = Transaction::fromArray(array_merge($this->minimalData(), [
            'correlation_source_id' => null,
            'correlation_type' => null,
            'voided_by' => null,
            'voided_at' => null,
            'void_reason' => null,
        ]));

        $this->assertNull($transaction->correlationSourceId);
        $this->assertNull($transaction->correlationType);
        $this->assertNull($transaction->voidedBy);
        $this->assertNull($transaction->voidedAt);
        $this->assertNull($transaction->voidReason);
    }

    #[Test]
    public function it_parses_a_payload_without_link_and_void_fields(): void
    {
        // An older server omits the fields entirely.
        $transaction = Transaction::fromArray($this->minimalData());

        $this->assertNull($transaction->correlationSourceId);
        $this->assertNull($transaction->correlationType);
        $this->assertNull($transaction->voidedBy);
        $this->assertNull($transaction->voidedAt);
        $this->assertNull($transaction->voidReason);
    }

    #[Test]
    public function it_handles_date_field_alias(): void
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'description' => 'Test',
            'date' => '2025-01-01T12:00:00Z',
            'layer' => 'settled',
            'status' => 'pending',
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];

        $transaction = Transaction::fromArray($data);

        $this->assertSame('2025-01-01', $transaction->effectiveDate->format('Y-m-d'));
    }

    #[Test]
    public function it_throws_value_error_on_unknown_layer(): void
    {
        $data = [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'description' => 'Test',
            'effective_date' => '2025-01-01T12:00:00Z',
            'layer' => 'archived',
            'status' => 'posted',
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];

        $this->expectException(\ValueError::class);
        $this->expectExceptionMessage('"archived" is not a valid backing value');

        Transaction::fromArray($data);
    }

    /**
     * @return array<string, mixed>
     */
    private function minimalData(): array
    {
        return [
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'ledger_id' => '123e4567-e89b-12d3-a456-426614174001',
            'description' => 'Test',
            'effective_date' => '2025-01-01T12:00:00Z',
            'layer' => 'encumbrance',
            'status' => 'posted',
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
    }
}
