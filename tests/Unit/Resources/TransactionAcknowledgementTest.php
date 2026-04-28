<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Resources;

use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Exceptions\LedgaException;
use Ledga\Api\Resources\TransactionAcknowledgement;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransactionAcknowledgementTest extends TestCase
{
    #[Test]
    public function from_array_hydrates_a_well_formed_payload(): void
    {
        $ack = TransactionAcknowledgement::fromArray([
            'id' => 'tx-1',
            'status' => 'pending',
            'idempotency_key' => 'idem-1',
            'correlation_id' => 'corr-1',
            'message' => 'Transaction accepted',
        ]);

        $this->assertSame('tx-1', $ack->id);
        $this->assertSame(TransactionStatus::Pending, $ack->status);
        $this->assertSame('idem-1', $ack->idempotencyKey);
        $this->assertSame('corr-1', $ack->correlationId);
        $this->assertSame('Transaction accepted', $ack->message);
    }

    #[Test]
    public function message_is_optional(): void
    {
        $ack = TransactionAcknowledgement::fromArray([
            'id' => 'tx-1',
            'status' => 'pending',
            'idempotency_key' => 'idem-1',
            'correlation_id' => 'corr-1',
        ]);

        $this->assertNull($ack->message);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function requiredFieldProvider(): array
    {
        return [
            'id' => ['id'],
            'status' => ['status'],
            'idempotency_key' => ['idempotency_key'],
            'correlation_id' => ['correlation_id'],
        ];
    }

    #[Test]
    #[DataProvider('requiredFieldProvider')]
    public function from_array_throws_when_required_field_is_null(string $field): void
    {
        $payload = [
            'id' => 'tx-1',
            'status' => 'pending',
            'idempotency_key' => 'idem-1',
            'correlation_id' => 'corr-1',
        ];
        $payload[$field] = null;

        $this->expectException(LedgaException::class);
        $this->expectExceptionMessage("missing or non-string '{$field}'");

        TransactionAcknowledgement::fromArray($payload);
    }

    #[Test]
    #[DataProvider('requiredFieldProvider')]
    public function from_array_throws_when_required_field_is_missing(string $field): void
    {
        $payload = [
            'id' => 'tx-1',
            'status' => 'pending',
            'idempotency_key' => 'idem-1',
            'correlation_id' => 'corr-1',
        ];
        unset($payload[$field]);

        $this->expectException(LedgaException::class);
        $this->expectExceptionMessage("missing or non-string '{$field}'");

        TransactionAcknowledgement::fromArray($payload);
    }
}
