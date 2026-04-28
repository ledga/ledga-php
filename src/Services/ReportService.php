<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Http\HttpClientInterface;

final class ReportService
{
    public function __construct(
        private readonly HttpClientInterface $http,
    ) {
    }

    /**
     * Get trial balance report.
     *
     * @param array<string, mixed> $params Report parameters (as_of_date, layer)
     * @return array<string, mixed>
     */
    public function trialBalance(array $params = []): array
    {
        $response = $this->http->get('reports/trial-balance', $params);

        return $response->unwrap();
    }

    /**
     * Get income statement report.
     *
     * @param array<string, mixed> $params Report parameters (start_date, end_date)
     * @return array<string, mixed>
     */
    public function incomeStatement(array $params = []): array
    {
        $response = $this->http->get('reports/income-statement', $params);

        return $response->unwrap();
    }
}
