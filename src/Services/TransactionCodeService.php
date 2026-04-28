<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
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
        return 'trancodes';
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
     * `code` must match `^[A-Z0-9_-]+$` (uppercase alphanumeric, dashes, underscores) and
     * is unique per ledger. Entry templates reference parameters via `{params.NAME}` —
     * bare `{NAME}` is reserved for system variables and will be rejected.
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
     * Full-replacement PUT — `name` and `entries_template` are required. The `code` and
     * `status` fields are immutable post-creation and are silently ignored if sent.
     * Use {@see self::deprecate()} to retire a trancode.
     *
     * @param array<string, mixed> $data Transaction code data to update
     */
    public function update(string $id, array $data): TransactionCode
    {
        return $this->updateRequest($this->basePath() . '/' . $id, $data);
    }

    /**
     * Mark a transaction code as deprecated. One-way transition: there is no reactivate
     * route, deprecated trancodes cannot be returned to active.
     */
    public function deprecate(string $id): TransactionCode
    {
        $response = $this->http->post($this->basePath() . '/' . $id . '/deprecate');

        return TransactionCode::fromArray($response->unwrap());
    }

    /**
     * Pre-flight a parameter payload against a trancode's `params_schema` without
     * creating a transaction. Returns true when the payload is valid; on failure the
     * server returns 422 and `LedgaValidationException` is thrown with the per-field
     * errors in `errorBody`.
     *
     * @param array<string, mixed> $params Parameter payload to validate.
     */
    public function validateParams(string $id, array $params): bool
    {
        $response = $this->http->post(
            $this->basePath() . '/' . $id . '/validate-params',
            ['params' => $params],
        );

        return (bool) ($response->unwrap()['valid'] ?? false);
    }
}
