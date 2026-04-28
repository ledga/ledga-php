<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Pagination;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Resources\Account;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class CursorPaginatorTest extends TestCase
{
    #[Test]
    public function it_iterates_through_single_page(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->with('accounts', [])
            ->willReturn(new Response(200, [
                'data' => [
                    $this->accountData('1'),
                    $this->accountData('2'),
                ],
                'meta' => ['pagination' => ['next_cursor' => null]],
            ]));

        $paginator = new CursorPaginator($http, 'accounts', [], Account::class);

        $accounts = [];
        foreach ($paginator as $account) {
            $accounts[] = $account;
        }

        $this->assertCount(2, $accounts);
        $this->assertInstanceOf(Account::class, $accounts[0]);
    }

    #[Test]
    public function it_iterates_through_multiple_pages(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->exactly(3))
            ->method('get')
            ->willReturnCallback(function (string $path, array $params) {
                $cursor = $params['cursor'] ?? null;

                if ($cursor === null) {
                    return new Response(200, [
                        'data' => [$this->accountData('1')],
                        'meta' => ['pagination' => ['next_cursor' => 'page2']],
                    ]);
                } elseif ($cursor === 'page2') {
                    return new Response(200, [
                        'data' => [$this->accountData('2')],
                        'meta' => ['pagination' => ['next_cursor' => 'page3']],
                    ]);
                } else {
                    return new Response(200, [
                        'data' => [$this->accountData('3')],
                        'meta' => ['pagination' => ['next_cursor' => null]],
                    ]);
                }
            });

        $paginator = new CursorPaginator($http, 'accounts', [], Account::class);

        $accounts = $paginator->toArray();

        $this->assertCount(3, $accounts);
        $this->assertSame('1', $accounts[0]->id);
        $this->assertSame('2', $accounts[1]->id);
        $this->assertSame('3', $accounts[2]->id);
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
