<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\Journal;

/**
 * @extends AbstractService<Journal>
 */
final class JournalService extends AbstractService
{
    protected function resourceClass(): string
    {
        return Journal::class;
    }

    protected function basePath(): string
    {
        return 'journals';
    }

    /**
     * List journals with manual pagination.
     *
     * @param array<string, mixed> $params Filter parameters (status, search, limit, cursor)
     * @return PaginatedResponse<Journal>
     */
    public function list(array $params = []): PaginatedResponse
    {
        return $this->listRequest($this->basePath(), $params);
    }

    /**
     * Iterate through all journals with auto-pagination.
     *
     * @param array<string, mixed> $params Filter parameters
     * @return CursorPaginator<Journal>
     */
    public function all(array $params = []): CursorPaginator
    {
        return $this->allRequest($this->basePath(), $params);
    }

    /**
     * Get a specific journal.
     */
    public function get(string $id): Journal
    {
        return $this->getRequest($this->basePath() . '/' . $id);
    }

    /**
     * Create a new journal.
     *
     * @param array<string, mixed> $data Journal data (code, name required)
     */
    public function create(array $data): Journal
    {
        return $this->createRequest($this->basePath(), $data);
    }

    /**
     * Update a journal.
     *
     * @param array<string, mixed> $data Journal data to update
     */
    public function update(string $id, array $data): Journal
    {
        return $this->updateRequest($this->basePath() . '/' . $id, $data);
    }

    /**
     * Delete a journal.
     */
    public function delete(string $id): void
    {
        $this->deleteRequest($this->basePath() . '/' . $id);
    }

    /**
     * Close a journal.
     */
    public function close(string $id): Journal
    {
        $response = $this->http->post($this->basePath() . '/' . $id . '/close');

        return Journal::fromArray($response->data);
    }
}
