<?php

declare(strict_types=1);

namespace Ledga\Api;

use Ledga\Api\Http\GuzzleHttpClient;
use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Services\AccountService;
use Ledga\Api\Services\AccountSetService;
use Ledga\Api\Services\JournalService;
use Ledga\Api\Services\ReportService;
use Ledga\Api\Services\TransactionCodeService;
use Ledga\Api\Services\TransactionService;

final class LedgaClient
{
    public readonly AccountService $accounts;
    public readonly AccountSetService $accountSets;
    public readonly JournalService $journals;
    public readonly TransactionService $transactions;
    public readonly TransactionCodeService $transactionCodes;
    public readonly ReportService $reports;

    private readonly Config $config;
    private readonly HttpClientInterface $http;

    /**
     * Create a new Ledga API client.
     *
     * @param string $apiKey Your Ledga API key
     * @param string|null $baseUrl Base URL for the API (defaults to https://ledga.io)
     * @param int $timeout Request timeout in seconds (defaults to 30)
     */
    public function __construct(
        string $apiKey,
        ?string $baseUrl = null,
        int $timeout = Config::DEFAULT_TIMEOUT,
    ) {
        $this->config = new Config($apiKey, $baseUrl, $timeout);
        $this->http = new GuzzleHttpClient($this->config);

        $this->accounts = new AccountService($this->http);
        $this->accountSets = new AccountSetService($this->http);
        $this->journals = new JournalService($this->http);
        $this->transactions = new TransactionService($this->http);
        $this->transactionCodes = new TransactionCodeService($this->http);
        $this->reports = new ReportService($this->http);
    }

    /**
     * Get the SDK version.
     *
     * Tracks the next anticipated release tag. Bump when cutting a release.
     */
    public static function version(): string
    {
        return '0.3.0-dev';
    }

    /**
     * Get the current configuration.
     */
    public function getConfig(): Config
    {
        return $this->config;
    }
}
