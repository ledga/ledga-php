<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\AccountSet;
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
}
