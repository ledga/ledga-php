<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use Ledga\Api\Enums\AccountSetMemberType;
use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\Account;
use Ledga\Api\Resources\AccountSet;
use Ledga\Api\Resources\AccountSetBalance;
use Ledga\Api\Resources\AccountSetMember;
use Ledga\Api\Services\AccountSetService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AccountSetServiceTest extends TestCase
{
    #[Test]
    public function it_creates_account_set_through_envelope(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('account-sets', ['code' => 'OPEX', 'name' => 'Operating Expenses'])
            ->willReturn(new Response(201, ['data' => $this->accountSetData('as-1', 'OPEX')]));

        $service = new AccountSetService($http);
        $set = $service->create(['code' => 'OPEX', 'name' => 'Operating Expenses']);

        $this->assertInstanceOf(AccountSet::class, $set);
        $this->assertSame('OPEX', $set->code);
    }

    #[Test]
    public function it_gets_account_set_through_envelope(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('account-sets/as-1')
            ->willReturn(new Response(200, ['data' => $this->accountSetData('as-1', 'OPEX')]));

        $service = new AccountSetService($http);
        $set = $service->get('as-1');

        $this->assertSame('as-1', $set->id);
    }

    #[Test]
    public function it_updates_account_set_through_envelope(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('put')
            ->with('account-sets/as-1', ['name' => 'Renamed'])
            ->willReturn(new Response(200, ['data' => $this->accountSetData('as-1', 'OPEX', 'Renamed')]));

        $service = new AccountSetService($http);
        $set = $service->update('as-1', ['name' => 'Renamed']);

        $this->assertSame('Renamed', $set->name);
    }

    #[Test]
    public function it_adds_account_member_to_set(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('post')
            ->with('account-sets/as-1/members', ['member_type' => 'account', 'member_id' => 'acc-1'])
            ->willReturn(new Response(200, ['data' => ['member_type' => 'account', 'member_id' => 'acc-1']]));

        $service = new AccountSetService($http);
        $member = $service->addMember('as-1', AccountSetMemberType::Account, 'acc-1');

        $this->assertInstanceOf(AccountSetMember::class, $member);
        $this->assertSame(AccountSetMemberType::Account, $member->memberType);
        $this->assertSame('acc-1', $member->memberId);
    }

    #[Test]
    public function it_adds_nested_set_member_to_set(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('post')
            ->with('account-sets/as-1/members', ['member_type' => 'account_set', 'member_id' => 'as-2'])
            ->willReturn(new Response(200, ['data' => ['member_type' => 'account_set', 'member_id' => 'as-2']]));

        $service = new AccountSetService($http);
        $member = $service->addMember('as-1', AccountSetMemberType::AccountSet, 'as-2');

        $this->assertSame(AccountSetMemberType::AccountSet, $member->memberType);
        $this->assertSame('as-2', $member->memberId);
    }

    #[Test]
    public function it_removes_member_from_set_via_delete_body(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('delete')
            ->with('account-sets/as-1/members', ['member_type' => 'account', 'member_id' => 'acc-1'])
            ->willReturn(new Response(200, ['data' => ['member_type' => 'account', 'member_id' => 'acc-1']]));

        $service = new AccountSetService($http);
        $member = $service->removeMember('as-1', AccountSetMemberType::Account, 'acc-1');

        $this->assertInstanceOf(AccountSetMember::class, $member);
        $this->assertSame(AccountSetMemberType::Account, $member->memberType);
        $this->assertSame('acc-1', $member->memberId);
    }

    #[Test]
    public function it_gets_all_accounts_in_set_as_flat_list(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('account-sets/as-1/accounts')
            ->willReturn(new Response(200, [
                'success' => true,
                'data' => [
                    $this->accountData('acc-1', '5000', 'Rent'),
                    $this->accountData('acc-2', '5100', 'Utilities'),
                ],
            ]));

        $service = new AccountSetService($http);
        $accounts = $service->getAccounts('as-1');

        $this->assertCount(2, $accounts);
        $this->assertContainsOnlyInstancesOf(Account::class, $accounts);
        $this->assertSame('5000', $accounts[0]->code);
        $this->assertSame('Utilities', $accounts[1]->name);
    }

    #[Test]
    public function it_returns_empty_list_for_set_with_no_accounts(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('account-sets/as-1/accounts')
            ->willReturn(new Response(200, ['success' => true, 'data' => []]));

        $service = new AccountSetService($http);

        $this->assertSame([], $service->getAccounts('as-1'));
    }

    #[Test]
    public function it_gets_aggregate_balance_for_set(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('get')
            ->with('account-sets/as-1/balance')
            ->willReturn(new Response(200, ['data' => [
                'account_set_id' => 'as-1',
                'account_set_code' => 'OPEX',
                'account_set_name' => 'Operating Expenses',
                'total_balance' => '1234.56',
                'currency' => 'GBP',
                'account_count' => 3,
            ]]));

        $service = new AccountSetService($http);
        $balance = $service->getBalance('as-1');

        $this->assertInstanceOf(AccountSetBalance::class, $balance);
        $this->assertSame('as-1', $balance->accountSetId);
        $this->assertSame('OPEX', $balance->accountSetCode);
        $this->assertSame('Operating Expenses', $balance->accountSetName);
        $this->assertSame('1234.56', $balance->totalBalance);
        $this->assertSame('GBP', $balance->currency);
        $this->assertSame(3, $balance->accountCount);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountSetData(string $id, string $code, string $name = 'Operating Expenses'): array
    {
        return [
            'id' => $id,
            'ledger_id' => 'ledger-1',
            'code' => $code,
            'name' => $name,
            'description' => null,
            'metadata' => null,
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
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
            'type' => 'expense',
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
