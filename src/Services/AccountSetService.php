<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\AccountSet;

/**
 * @extends AbstractService<AccountSet>
 */
final class AccountSetService extends AbstractService
{
    protected function resourceClass(): string
    {
        return AccountSet::class;
    }

    protected function basePath(): string
    {
        return 'account-sets';
    }

    /**
     * List account sets with manual pagination.
     *
     * @param array<string, mixed> $params Filter parameters (search, limit, cursor)
     * @return PaginatedResponse<AccountSet>
     */
    public function list(array $params = []): PaginatedResponse
    {
        return $this->listRequest($this->basePath(), $params);
    }

    /**
     * Iterate through all account sets with auto-pagination.
     *
     * @param array<string, mixed> $params Filter parameters
     * @return CursorPaginator<AccountSet>
     */
    public function all(array $params = []): CursorPaginator
    {
        return $this->allRequest($this->basePath(), $params);
    }

    /**
     * Get a specific account set.
     */
    public function get(string $id): AccountSet
    {
        return $this->getRequest($this->basePath() . '/' . $id);
    }

    /**
     * Create a new account set.
     *
     * @param array<string, mixed> $data Account set data (code, name required)
     */
    public function create(array $data): AccountSet
    {
        return $this->createRequest($this->basePath(), $data);
    }

    /**
     * Update an account set.
     *
     * @param array<string, mixed> $data Account set data to update
     */
    public function update(string $id, array $data): AccountSet
    {
        return $this->updateRequest($this->basePath() . '/' . $id, $data);
    }

    /**
     * Delete an account set.
     */
    public function delete(string $id): void
    {
        $this->deleteRequest($this->basePath() . '/' . $id);
    }
}
