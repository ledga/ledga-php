<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

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
    }

    /**
     * @return array<string, mixed>
     */
    private function codeData(string $id, string $code): array
    {
        return [
            'id' => $id,
            'ledger_id' => 'ledger-1',
            'code' => $code,
            'name' => 'Invoice',
            'description' => null,
            'status' => 'active',
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
