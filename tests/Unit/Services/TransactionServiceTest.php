<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\BatchResponse;
use Ledga\Api\Resources\TransactionAcknowledgement;
use Ledga\Api\Services\TransactionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransactionServiceTest extends TestCase
{
    #[Test]
    public function it_returns_an_acknowledgement_from_create(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('transactions', $this->anything())
            ->willReturn(new Response(202, ['data' => $this->ackData()]));

        $service = new TransactionService($http);
        $ack = $service->create([
            'description' => 'Payment received',
            'effective_date' => '2025-01-01',
            'idempotency_key' => 'idem-1',
            'entries' => [
                ['account_code' => '1000', 'type' => 'debit', 'amount' => '100.00'],
                ['account_code' => '4000', 'type' => 'credit', 'amount' => '100.00'],
            ],
        ]);

        $this->assertInstanceOf(TransactionAcknowledgement::class, $ack);
        $this->assertSame('tx-123', $ack->id);
        $this->assertSame(TransactionStatus::Pending, $ack->status);
        $this->assertSame('idem-1', $ack->idempotencyKey);
    }

    #[Test]
    public function it_creates_from_trancode(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('post')
            ->with('transactions', [
                'description' => 'Customer payment',
                'effective_date' => '2026-04-28',
                'idempotency_key' => 'idem-1',
                'transaction_code' => 'BOOK_TRANSFER',
                'transaction_code_params' => [
                    'amount' => '100.00',
                    'from_account' => '1000',
                    'to_account' => '4000',
                ],
            ])
            ->willReturn(new Response(202, ['data' => $this->ackData()]));

        $service = new TransactionService($http);
        $ack = $service->createFromCode(
            'BOOK_TRANSFER',
            [
                'amount' => '100.00',
                'from_account' => '1000',
                'to_account' => '4000',
            ],
            [
                'description' => 'Customer payment',
                'effective_date' => '2026-04-28',
                'idempotency_key' => 'idem-1',
            ],
        );

        $this->assertInstanceOf(TransactionAcknowledgement::class, $ack);
        $this->assertSame('tx-123', $ack->id);
        $this->assertSame(TransactionStatus::Pending, $ack->status);
    }

    /**
     * @return array<int, array{0: string}>
     */
    public static function reservedExtrasProvider(): array
    {
        return [
            'entries belongs to direct-entry mode' => ['entries'],
            'transaction_code is set by the method' => ['transaction_code'],
            'transaction_code_params is set by the method' => ['transaction_code_params'],
        ];
    }

    #[Test]
    #[DataProvider('reservedExtrasProvider')]
    public function create_from_trancode_rejects_reserved_keys_in_extras(string $reservedKey): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->never())->method('post');

        $service = new TransactionService($http);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("\$extra must not contain '{$reservedKey}'");

        $service->createFromCode(
            'BOOK_TRANSFER',
            ['amount' => '50.00'],
            [
                $reservedKey => 'whatever',
                'description' => 'x',
                'effective_date' => '2026-04-28',
                'idempotency_key' => 'idem-2',
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function ackData(): array
    {
        return [
            'id' => 'tx-123',
            'status' => 'pending',
            'idempotency_key' => 'idem-1',
            'correlation_id' => 'corr-1',
            'message' => 'Transaction accepted and processing',
        ];
    }

    #[Test]
    public function it_reverses_transaction(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('transactions/tx-123/reverse', [
                'reason' => 'Customer refund',
                'date' => '2025-01-02',
            ])
            ->willReturn(new Response(201, [
                'data' => array_merge(
                    $this->transactionData(),
                    [
                        'id' => 'tx-456',
                        'original_transaction_id' => 'tx-123',
                        'reversal_reason' => 'Customer refund',
                    ],
                ),
            ]));

        $service = new TransactionService($http);
        $reversal = $service->reverse('tx-123', [
            'reason' => 'Customer refund',
            'date' => '2025-01-02',
        ]);

        $this->assertSame('tx-456', $reversal->id);
        $this->assertSame('tx-123', $reversal->originalTransactionId);
        $this->assertSame('Customer refund', $reversal->reversalReason);
    }

    #[Test]
    public function it_creates_batch_transactions(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('transactions/batch', [
                'transactions' => [
                    [
                        'idempotency_key' => 'tx-001',
                        'description' => 'Payment 1',
                        'effective_date' => '2025-01-15',
                        'entries' => [
                            ['account_code' => '1000', 'type' => 'debit', 'amount' => '100.00'],
                            ['account_code' => '4000', 'type' => 'credit', 'amount' => '100.00'],
                        ],
                    ],
                    [
                        'idempotency_key' => 'tx-002',
                        'description' => 'Payment 2',
                        'effective_date' => '2025-01-15',
                        'entries' => [
                            ['account_code' => '1000', 'type' => 'debit', 'amount' => '200.00'],
                            ['account_code' => '4000', 'type' => 'credit', 'amount' => '200.00'],
                        ],
                    ],
                ],
            ])
            ->willReturn(new Response(202, [
                'success' => true,
                'data' => [
                    'results' => [
                        [
                            'idempotency_key' => 'tx-001',
                            'status' => 'accepted',
                            'id' => 'uuid-1',
                            'correlation_id' => null,
                            'error' => null,
                            'error_code' => null,
                        ],
                        [
                            'idempotency_key' => 'tx-002',
                            'status' => 'accepted',
                            'id' => 'uuid-2',
                            'correlation_id' => null,
                            'error' => null,
                            'error_code' => null,
                        ],
                    ],
                    'summary' => [
                        'total' => 2,
                        'accepted' => 2,
                        'rejected' => 0,
                    ],
                ],
            ]));

        $service = new TransactionService($http);
        $response = $service->createBatch([
            [
                'idempotency_key' => 'tx-001',
                'description' => 'Payment 1',
                'effective_date' => '2025-01-15',
                'entries' => [
                    ['account_code' => '1000', 'type' => 'debit', 'amount' => '100.00'],
                    ['account_code' => '4000', 'type' => 'credit', 'amount' => '100.00'],
                ],
            ],
            [
                'idempotency_key' => 'tx-002',
                'description' => 'Payment 2',
                'effective_date' => '2025-01-15',
                'entries' => [
                    ['account_code' => '1000', 'type' => 'debit', 'amount' => '200.00'],
                    ['account_code' => '4000', 'type' => 'credit', 'amount' => '200.00'],
                ],
            ],
        ]);

        $this->assertInstanceOf(BatchResponse::class, $response);
        $this->assertSame(2, $response->total);
        $this->assertSame(2, $response->accepted);
        $this->assertSame(0, $response->rejected);
        $this->assertTrue($response->allAccepted());
        $this->assertFalse($response->hasRejections());
        $this->assertCount(2, $response->results);
        $this->assertSame('uuid-1', $response->results[0]->id);
        $this->assertTrue($response->results[0]->isAccepted());
    }

    #[Test]
    public function it_handles_partial_batch_failure(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->willReturn(new Response(202, [
                'success' => true,
                'data' => [
                    'results' => [
                        [
                            'idempotency_key' => 'tx-001',
                            'status' => 'accepted',
                            'id' => 'uuid-1',
                            'correlation_id' => null,
                            'error' => null,
                            'error_code' => null,
                        ],
                        [
                            'idempotency_key' => 'tx-002',
                            'status' => 'rejected',
                            'id' => null,
                            'correlation_id' => null,
                            'error' => 'Transaction does not balance',
                            'error_code' => 'UNBALANCED',
                        ],
                    ],
                    'summary' => [
                        'total' => 2,
                        'accepted' => 1,
                        'rejected' => 1,
                    ],
                ],
            ]));

        $service = new TransactionService($http);
        $response = $service->createBatch([]);

        $this->assertSame(2, $response->total);
        $this->assertSame(1, $response->accepted);
        $this->assertSame(1, $response->rejected);
        $this->assertFalse($response->allAccepted());
        $this->assertTrue($response->hasRejections());

        $accepted = $response->getAccepted();
        $rejected = $response->getRejected();

        $this->assertCount(1, $accepted);
        $this->assertCount(1, $rejected);
        $this->assertSame('Transaction does not balance', $rejected[1]->error);
        $this->assertSame('UNBALANCED', $rejected[1]->errorCode);
    }

    /**
     * @return array<string, mixed>
     */
    private function transactionData(): array
    {
        return [
            'id' => 'tx-123',
            'ledger_id' => 'ledger-1',
            'journal_id' => null,
            'reference' => 'REF-001',
            'description' => 'Payment received',
            'effective_date' => '2025-01-01T12:00:00Z',
            'layer' => 'settled',
            'status' => 'posted',
            'total_amount' => '100.00',
            'entry_count' => 2,
            'hash' => 'abc123',
            'previous_hash' => null,
            'correlation_id' => null,
            'metadata' => null,
            'original_transaction_id' => null,
            'reversal_reason' => null,
            'entries' => [],
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
    }
}
