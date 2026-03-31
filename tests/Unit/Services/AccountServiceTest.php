<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\Account;
use Ledga\Api\Resources\AccountBalance;
use Ledga\Api\Resources\AccountEntry;
use Ledga\Api\Resources\BalanceHistory;
use Ledga\Api\Services\AccountService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccountServiceTest extends TestCase
{
    #[Test]
    public function it_lists_accounts(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts', [])
            ->willReturn(new Response(200, [
                'data' => [
                    $this->accountData('1', '1000', 'Cash'),
                    $this->accountData('2', '1100', 'Bank'),
                ],
                'meta' => [
                    'next_cursor' => 'cursor123',
                    'per_page' => 25,
                ],
            ]));

        $service = new AccountService($http);
        $result = $service->list();

        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(Account::class, $result->data[0]);
        $this->assertSame('1000', $result->data[0]->code);
        $this->assertTrue($result->hasMore());
    }

    #[Test]
    public function it_gets_single_account(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts/123')
            ->willReturn(new Response(200, $this->accountData('123', '1000', 'Cash')));

        $service = new AccountService($http);
        $account = $service->get('123');

        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame('123', $account->id);
        $this->assertSame('1000', $account->code);
    }

    #[Test]
    public function it_creates_account(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('accounts', ['code' => '2000', 'name' => 'Inventory', 'type' => 'asset'])
            ->willReturn(new Response(201, $this->accountData('456', '2000', 'Inventory')));

        $service = new AccountService($http);
        $account = $service->create([
            'code' => '2000',
            'name' => 'Inventory',
            'type' => 'asset',
        ]);

        $this->assertSame('456', $account->id);
        $this->assertSame('2000', $account->code);
    }

    #[Test]
    public function it_updates_account(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('put')
            ->with('accounts/123', ['name' => 'Updated Name'])
            ->willReturn(new Response(200, $this->accountData('123', '1000', 'Updated Name')));

        $service = new AccountService($http);
        $account = $service->update('123', ['name' => 'Updated Name']);

        $this->assertSame('Updated Name', $account->name);
    }

    #[Test]
    public function it_deletes_account(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('delete')
            ->with('accounts/123')
            ->willReturn(new Response(200, []));

        $service = new AccountService($http);
        $service->delete('123');
    }

    #[Test]
    public function it_gets_account_balance(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts/123/balance', [])
            ->willReturn(new Response(200, [
                'settled' => '1000.00',
                'pending' => '50.00',
                'encumbered' => '100.00',
                'available' => '850.00',
            ]));

        $service = new AccountService($http);
        $balance = $service->getBalance('123');

        $this->assertInstanceOf(AccountBalance::class, $balance);
        $this->assertSame('1000.00', $balance->settled);
        $this->assertSame('850.00', $balance->available);
    }

    #[Test]
    public function it_gets_account_by_code(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts/code/1000')
            ->willReturn(new Response(200, $this->accountData('123', '1000', 'Cash')));

        $service = new AccountService($http);
        $account = $service->getByCode('1000');

        $this->assertInstanceOf(Account::class, $account);
        $this->assertSame('123', $account->id);
        $this->assertSame('1000', $account->code);
    }

    #[Test]
    public function it_gets_balance_by_code(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts/code/1000/balance', [])
            ->willReturn(new Response(200, [
                'settled' => '1000.00',
                'pending' => '50.00',
                'encumbered' => '100.00',
                'available' => '850.00',
            ]));

        $service = new AccountService($http);
        $balance = $service->getBalanceByCode('1000');

        $this->assertInstanceOf(AccountBalance::class, $balance);
        $this->assertSame('1000.00', $balance->settled);
        $this->assertSame('850.00', $balance->available);
    }

    #[Test]
    public function it_gets_balance_history(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts/123/balance-history', ['start_date' => '2025-01-01'])
            ->willReturn(new Response(200, [
                'account' => ['id' => '123', 'code' => '1000', 'name' => 'Cash'],
                'period' => ['start' => '2025-01-01', 'end' => '2025-01-31'],
                'history' => [
                    ['date' => '2025-01-01', 'balance' => '100.00'],
                    ['date' => '2025-01-15', 'balance' => '500.00'],
                ],
                'ending_balance' => '500.00',
            ]));

        $service = new AccountService($http);
        $history = $service->getBalanceHistory('123', ['start_date' => '2025-01-01']);

        $this->assertInstanceOf(BalanceHistory::class, $history);
        $this->assertSame('500.00', $history->endingBalance);
        $this->assertCount(2, $history->history);
    }

    #[Test]
    public function it_gets_account_entries(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('accounts/123/entries', [])
            ->willReturn(new Response(200, [
                'data' => [
                    [
                        'id' => 'entry-1',
                        'transaction_id' => 'tx-1',
                        'account_id' => '123',
                        'account_code' => '1000',
                        'type' => 'debit',
                        'amount' => '100.00',
                        'description' => 'Payment received',
                        'layer' => 'SETTLED',
                        'effective_date' => '2025-01-01',
                        'balance_after' => '100.00',
                        'transaction' => [
                            'reference' => 'REF-001',
                            'description' => 'Customer payment',
                        ],
                    ],
                    [
                        'id' => 'entry-2',
                        'transaction_id' => 'tx-2',
                        'account_id' => '123',
                        'account_code' => '1000',
                        'type' => 'debit',
                        'amount' => '50.00',
                        'description' => null,
                        'layer' => 'SETTLED',
                        'effective_date' => '2025-01-02',
                        'balance_after' => '150.00',
                        'transaction' => null,
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'next_cursor' => null,
                        'per_page' => 25,
                    ],
                ],
            ]));

        $service = new AccountService($http);
        $result = $service->getEntries('123');

        $this->assertCount(2, $result->data);
        $this->assertInstanceOf(AccountEntry::class, $result->data[0]);
        $this->assertSame('100.00', $result->data[0]->balanceAfter);
        $this->assertSame('REF-001', $result->data[0]->transaction?->reference);
        $this->assertSame('150.00', $result->data[1]->balanceAfter);
        $this->assertNull($result->data[1]->transaction);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountData(string $id, string $code, string $name): array
    {
        return [
            'id' => $id,
            'ledger_id' => 'ledger-1',
            'code' => $code,
            'name' => $name,
            'type' => 'asset',
            'normal_balance' => 'debit',
            'balance' => '0.00',
            'is_active' => true,
            'is_system' => false,
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
    }
}
