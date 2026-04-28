<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\Transaction;
use Ledga\Api\Resources\TransactionCode;

/**
 * @extends AbstractService<TransactionCode>
 */
final class TransactionCodeService extends AbstractService
{
    protected function resourceClass(): string
    {
        return TransactionCode::class;
    }

    protected function basePath(): string
    {
        return 'transaction-codes';
    }

    /**
     * List transaction codes with manual pagination.
     *
     * @param array<string, mixed> $params Filter parameters (status, search, limit, cursor)
     * @return PaginatedResponse<TransactionCode>
     */
    public function list(array $params = []): PaginatedResponse
    {
        return $this->listRequest($this->basePath(), $params);
    }

    /**
     * Iterate through all transaction codes with auto-pagination.
     *
     * @param array<string, mixed> $params Filter parameters
     * @return CursorPaginator<TransactionCode>
     */
    public function all(array $params = []): CursorPaginator
    {
        return $this->allRequest($this->basePath(), $params);
    }

    /**
     * Get a specific transaction code.
     */
    public function get(string $id): TransactionCode
    {
        return $this->getRequest($this->basePath() . '/' . $id);
    }

    /**
     * Create a new transaction code.
     *
     * @param array<string, mixed> $data Transaction code data (code, name, entries_template required)
     */
    public function create(array $data): TransactionCode
    {
        return $this->createRequest($this->basePath(), $data);
    }

    /**
     * Update a transaction code.
     *
     * @param array<string, mixed> $data Transaction code data to update
     */
    public function update(string $id, array $data): TransactionCode
    {
        return $this->updateRequest($this->basePath() . '/' . $id, $data);
    }

    /**
     * Delete a transaction code.
     */
    public function delete(string $id): void
    {
        $this->deleteRequest($this->basePath() . '/' . $id);
    }

    /**
     * Execute a transaction code with parameters.
     *
     * @param array<string, mixed> $params Parameters for the transaction code
     */
    public function execute(string $id, array $params): Transaction
    {
        $response = $this->http->post($this->basePath() . '/' . $id . '/execute', $params);

        return Transaction::fromArray($response->unwrap());
    }
}
