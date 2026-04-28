<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Pagination;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\Account;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PaginatedResponseTest extends TestCase
{
    #[Test]
    public function it_reads_cursor_metadata_from_meta_pagination(): void
    {
        $http = $this->createMock(HttpClientInterface::class);

        $page = new PaginatedResponse(
            [
                'data' => [$this->accountData('1')],
                'meta' => [
                    'pagination' => [
                        'limit' => 50,
                        'has_more' => true,
                        'next_cursor' => 'next-token',
                        'previous_cursor' => 'prev-token',
                    ],
                ],
            ],
            Account::class,
            $http,
            'accounts',
        );

        $this->assertSame('next-token', $page->nextCursor);
        $this->assertSame('prev-token', $page->prevCursor);
        $this->assertSame(50, $page->perPage);
        $this->assertTrue($page->hasMore());
        $this->assertTrue($page->hasPrevious());
    }

    #[Test]
    public function it_defaults_per_page_when_meta_pagination_omits_limit(): void
    {
        $http = $this->createMock(HttpClientInterface::class);

        $page = new PaginatedResponse(
            ['data' => [], 'meta' => ['pagination' => []]],
            Account::class,
            $http,
            'accounts',
        );

        $this->assertSame(25, $page->perPage);
        $this->assertNull($page->nextCursor);
        $this->assertNull($page->prevCursor);
        $this->assertFalse($page->hasMore());
        $this->assertFalse($page->hasPrevious());
    }

    #[Test]
    public function previous_page_round_trips_the_previous_cursor(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->with('accounts', ['cursor' => 'prev-token'])
            ->willReturn(new Response(200, [
                'data' => [$this->accountData('older')],
                'meta' => ['pagination' => ['next_cursor' => null, 'previous_cursor' => null]],
            ]));

        $page = new PaginatedResponse(
            [
                'data' => [$this->accountData('current')],
                'meta' => ['pagination' => ['next_cursor' => null, 'previous_cursor' => 'prev-token']],
            ],
            Account::class,
            $http,
            'accounts',
        );

        $previous = $page->previousPage();

        $this->assertNotNull($previous);
        $this->assertSame('older', $previous->data[0]->id);
    }

    #[Test]
    public function previous_page_returns_null_when_no_previous_cursor(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->never())->method('get');

        $page = new PaginatedResponse(
            [
                'data' => [$this->accountData('first')],
                'meta' => ['pagination' => ['next_cursor' => 'n', 'previous_cursor' => null]],
            ],
            Account::class,
            $http,
            'accounts',
        );

        $this->assertNull($page->previousPage());
    }

    /**
     * @return array<string, mixed>
     */
    private function accountData(string $id): array
    {
        return [
            'id' => $id,
            'ledger_id' => 'ledger-1',
            'code' => '1000',
            'name' => 'Test',
            'type' => 'asset',
            'normal_balance' => 'debit',
            'category' => 'system',
            'balance' => '0.00',
            'is_active' => true,
            'is_system' => false,
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
    }
}
