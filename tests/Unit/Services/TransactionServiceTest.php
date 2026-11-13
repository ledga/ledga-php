<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Ledga\Api\Config;
use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Http\GuzzleHttpClient;
use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\BatchResponse;
use Ledga\Api\Resources\TransactionAcknowledgement;
use Ledga\Api\Services\TransactionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

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
    public function it_lists_a_correlation_trail_by_query_parameter(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->with('transactions/correlation', ['limit' => 10, 'correlation_id' => 'INVOICE-42'])
            ->willReturn(new Response(200, $this->page(
                [
                    $this->transactionData(['id' => 'tx-1', 'correlation_id' => 'INVOICE-42']),
                    $this->transactionData([
                        'id' => 'tx-2',
                        'correlation_id' => 'INVOICE-42',
                        'correlation_source_id' => 'tx-1',
                        'correlation_type' => 'reversal',
                    ]),
                ],
                'cursor-2',
            )));

        $service = new TransactionService($http);
        $page = $service->byCorrelation('INVOICE-42', ['limit' => 10]);

        $this->assertCount(2, $page->data);
        $this->assertSame('tx-1', $page->data[0]->id);
        $this->assertSame('tx-1', $page->data[1]->correlationSourceId);
        $this->assertSame('reversal', $page->data[1]->correlationType);
        $this->assertTrue($page->hasMore());
    }

    #[Test]
    public function the_correlation_id_argument_wins_over_one_in_params(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->with('transactions/correlation', ['correlation_id' => 'INVOICE-42'])
            ->willReturn(new Response(200, $this->page([], null)));

        $service = new TransactionService($http);
        $page = $service->byCorrelation('INVOICE-42', ['correlation_id' => 'OTHER']);

        $this->assertSame([], $page->data);
    }

    #[Test]
    public function by_correlation_next_page_keeps_the_correlation_id(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $path, array $query): Response {
                $this->assertSame('transactions/correlation', $path);
                $this->assertSame('INVOICE-42', $query['correlation_id']);

                return isset($query['cursor'])
                    ? new Response(200, $this->page([$this->transactionData(['id' => 'tx-2'])], null))
                    : new Response(200, $this->page([$this->transactionData(['id' => 'tx-1'])], 'cursor-2'));
            });

        $service = new TransactionService($http);
        $next = $service->byCorrelation('INVOICE-42')->nextPage();

        $this->assertNotNull($next);
        $this->assertSame('tx-2', $next->data[0]->id);
    }

    #[Test]
    public function all_by_correlation_follows_every_page(): void
    {
        $calls = [];
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->exactly(2))
            ->method('get')
            ->willReturnCallback(function (string $path, array $query) use (&$calls): Response {
                $calls[] = [$path, $query];

                return isset($query['cursor'])
                    ? new Response(200, $this->page([$this->transactionData(['id' => 'tx-2'])], null))
                    : new Response(200, $this->page([$this->transactionData(['id' => 'tx-1'])], 'cursor-2'));
            });

        $service = new TransactionService($http);
        $ids = array_map(
            static fn ($tx) => $tx->id,
            $service->allByCorrelation('INVOICE-42', ['limit' => 1])->toArray(),
        );

        $this->assertSame(['tx-1', 'tx-2'], $ids);
        $this->assertSame([
            ['transactions/correlation', ['limit' => 1, 'correlation_id' => 'INVOICE-42']],
            ['transactions/correlation', ['limit' => 1, 'correlation_id' => 'INVOICE-42', 'cursor' => 'cursor-2']],
        ], $calls);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function reservedCorrelationIdProvider(): array
    {
        return [
            'slash' => ['INV/42', 'correlation_id=INV%2F42'],
            'percent' => ['50%off', 'correlation_id=50%25off'],
            'space' => ['order 42', 'correlation_id=order%2042'],
            'ampersand and equals' => ['a&b=c', 'correlation_id=a%26b%3Dc'],
        ];
    }

    #[Test]
    #[DataProvider('reservedCorrelationIdProvider')]
    public function it_sends_the_correlation_id_url_encoded_in_the_query_string(
        string $correlationId,
        string $expectedQuery,
    ): void {
        /** @var array<int, array{request: RequestInterface}> $history */
        $history = [];
        $stack = HandlerStack::create(new MockHandler([
            new GuzzleResponse(200, [], (string) json_encode($this->page([], null))),
        ]));
        $stack->push(Middleware::history($history));
        $http = new GuzzleHttpClient(
            new Config('test-api-key', 'https://test.ledga.io'),
            new Client(['handler' => $stack, 'base_uri' => 'https://test.ledga.io/api/v1/']),
        );

        (new TransactionService($http))->byCorrelation($correlationId);

        $this->assertCount(1, $history);
        $uri = $history[0]['request']->getUri();
        $this->assertSame('/api/v1/transactions/correlation', $uri->getPath());
        $this->assertSame($expectedQuery, $uri->getQuery());
        parse_str($uri->getQuery(), $decoded);
        $this->assertSame(['correlation_id' => $correlationId], $decoded);
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
     * @param array<int, array<string, mixed>> $items
     * @return array<string, mixed>
     */
    private function page(array $items, ?string $nextCursor): array
    {
        return [
            'success' => true,
            'data' => $items,
            'meta' => [
                'pagination' => [
                    'limit' => 25,
                    'has_more' => $nextCursor !== null,
                    'next_cursor' => $nextCursor,
                    'previous_cursor' => null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function transactionData(array $overrides = []): array
    {
        return array_merge([
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
        ], $overrides);
    }
}
