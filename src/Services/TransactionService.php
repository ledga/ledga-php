<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\BatchResponse;
use Ledga\Api\Resources\Transaction;

/**
 * @extends AbstractService<Transaction>
 */
final class TransactionService extends AbstractService
{
    protected function resourceClass(): string
    {
        return Transaction::class;
    }

    protected function basePath(): string
    {
        return 'transactions';
    }

    /**
     * List transactions with manual pagination.
     *
     * @param array<string, mixed> $params Filter parameters (status, layer, start_date, end_date, etc.)
     * @return PaginatedResponse<Transaction>
     */
    public function list(array $params = []): PaginatedResponse
    {
        return $this->listRequest($this->basePath(), $params);
    }

    /**
     * Iterate through all transactions with auto-pagination.
     *
     * @param array<string, mixed> $params Filter parameters
     * @return CursorPaginator<Transaction>
     */
    public function all(array $params = []): CursorPaginator
    {
        return $this->allRequest($this->basePath(), $params);
    }

    /**
     * Get a specific transaction.
     */
    public function get(string $id): Transaction
    {
        return $this->getRequest($this->basePath() . '/' . $id);
    }

    /**
     * Create a new transaction.
     *
     * @param array<string, mixed> $data Transaction data including entries
     */
    public function create(array $data): Transaction
    {
        return $this->createRequest($this->basePath(), $data);
    }

    /**
     * Reverse a transaction.
     *
     * @param array<string, mixed> $data Reversal data (reason, date required)
     */
    public function reverse(string $id, array $data): Transaction
    {
        $response = $this->http->post($this->basePath() . '/' . $id . '/reverse', $data);

        return Transaction::fromArray($response->data);
    }

    /**
     * Create multiple transactions in a batch.
     *
     * Each transaction is processed independently, supporting partial success.
     * Maximum 100 transactions per batch (configurable on server).
     *
     * @param array<int, array<string, mixed>> $transactions Array of transaction data
     */
    public function createBatch(array $transactions): BatchResponse
    {
        $response = $this->http->post($this->basePath() . '/batch', [
            'transactions' => $transactions,
        ]);

        return BatchResponse::fromArray($response->data);
    }
}
