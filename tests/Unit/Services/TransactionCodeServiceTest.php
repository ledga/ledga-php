<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use Ledga\Api\Enums\TransactionCodeStatus;
use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\TransactionCode;
use Ledga\Api\Services\TransactionCodeService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TransactionCodeServiceTest extends TestCase
{
    #[Test]
    public function it_targets_the_trancodes_route(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('get')
            ->with('trancodes/tc-1')
            ->willReturn(new Response(200, ['data' => $this->codeData('tc-1', 'INVOICE')]));

        $service = new TransactionCodeService($http);
        $code = $service->get('tc-1');

        $this->assertInstanceOf(TransactionCode::class, $code);
        $this->assertSame('INVOICE', $code->code);
        $this->assertSame(TransactionCodeStatus::Active, $code->status);
    }

    #[Test]
    public function it_deprecates_through_envelope(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->expects($this->once())
            ->method('post')
            ->with('trancodes/tc-1/deprecate')
            ->willReturn(new Response(200, ['data' => $this->codeData('tc-1', 'INVOICE', 'deprecated')]));

        $service = new TransactionCodeService($http);
        $code = $service->deprecate('tc-1');

        $this->assertSame(TransactionCodeStatus::Deprecated, $code->status);
    }

    /**
     * @return array<string, mixed>
     */
    private function codeData(string $id, string $code, string $status = 'active'): array
    {
        return [
            'id' => $id,
            'ledger_id' => 'ledger-1',
            'code' => $code,
            'name' => 'Invoice',
            'description' => null,
            'status' => $status,
            'version' => 1,
            'params_schema' => null,
            'entries_template' => [],
            'validation_rules' => null,
            'metadata' => null,
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
    }
}
