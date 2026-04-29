<?php

declare(strict_types=1);

namespace Ledga\Api\Tests\Unit;

use Ledga\Api\LedgaClient;
use Ledga\Api\Services\AccountService;
use Ledga\Api\Services\AccountSetService;
use Ledga\Api\Services\JournalService;
use Ledga\Api\Services\ReportService;
use Ledga\Api\Services\TransactionCodeService;
use Ledga\Api\Services\TransactionService;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class LedgaClientTest extends TestCase
{
    #[Test]
    public function it_creates_client_with_default_config(): void
    {
        $client = new LedgaClient('test-api-key');

        $this->assertSame('https://ledga.io', $client->getConfig()->baseUrl);
        $this->assertSame(30, $client->getConfig()->timeout);
    }

    #[Test]
    public function it_creates_client_with_custom_config(): void
    {
        $client = new LedgaClient(
            'test-api-key',
            'https://custom.ledga.io',
            60
        );

        $this->assertSame('https://custom.ledga.io', $client->getConfig()->baseUrl);
        $this->assertSame(60, $client->getConfig()->timeout);
    }

    #[Test]
    public function it_exposes_all_services(): void
    {
        $client = new LedgaClient('test-api-key');

        $this->assertInstanceOf(AccountService::class, $client->accounts);
        $this->assertInstanceOf(AccountSetService::class, $client->accountSets);
        $this->assertInstanceOf(JournalService::class, $client->journals);
        $this->assertInstanceOf(TransactionService::class, $client->transactions);
        $this->assertInstanceOf(TransactionCodeService::class, $client->transactionCodes);
        $this->assertInstanceOf(ReportService::class, $client->reports);
    }

    #[Test]
    public function it_returns_the_installed_composer_version(): void
    {
        $expected = \Composer\InstalledVersions::getPrettyVersion('ledga/ledga-php') ?? 'dev';

        $this->assertSame($expected, LedgaClient::version());
    }
}
