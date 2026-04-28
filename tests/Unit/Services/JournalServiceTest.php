<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit\Services;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Http\Response;
use Ledga\Api\Resources\Journal;
use Ledga\Api\Services\JournalService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class JournalServiceTest extends TestCase
{
    #[Test]
    public function it_creates_journal_through_envelope(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('journals', ['code' => 'GL', 'name' => 'General'])
            ->willReturn(new Response(201, ['data' => $this->journalData('j-1', 'active')]));

        $service = new JournalService($http);
        $journal = $service->create(['code' => 'GL', 'name' => 'General']);

        $this->assertInstanceOf(Journal::class, $journal);
        $this->assertSame('j-1', $journal->id);
    }

    #[Test]
    public function it_closes_journal_through_envelope(): void
    {
        $http = $this->createMock(HttpClientInterface::class);
        $http->method('post')
            ->with('journals/j-1/close')
            ->willReturn(new Response(200, ['data' => $this->journalData('j-1', 'closed')]));

        $service = new JournalService($http);
        $journal = $service->close('j-1');

        $this->assertSame('closed', $journal->status->value);
    }

    /**
     * @return array<string, mixed>
     */
    private function journalData(string $id, string $status): array
    {
        return [
            'id' => $id,
            'ledger_id' => 'ledger-1',
            'code' => 'GL',
            'name' => 'General',
            'description' => null,
            'status' => $status,
            'metadata' => null,
            'created_at' => '2025-01-01T12:00:00Z',
            'updated_at' => '2025-01-01T12:00:00Z',
        ];
    }
}
