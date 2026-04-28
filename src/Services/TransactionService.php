<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\BatchResponse;
use Ledga\Api\Resources\Transaction;
use Ledga\Api\Resources\TransactionAcknowledgement;

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
     * Submit a new transaction with explicit entries (Mode 1).
     *
     * Returns a `TransactionAcknowledgement` — the server has accepted the request
     * but not yet committed entries. Poll with `transactions->get($ack->id)` once you
     * need the durable transaction record. For trancode-driven posting (Mode 2) use
     * {@see self::createFromCode()} instead; the two modes are mutually exclusive.
     *
     * @param array<string, mixed> $data Transaction data — `description`, `effective_date`,
     *                                   `idempotency_key`, `entries` required;
     *                                   `layer`, `journal_id`, `correlation_id`,
     *                                   `correlation_type`, `metadata` optional.
     */
    public function create(array $data): TransactionAcknowledgement
    {
        $response = $this->http->post($this->basePath(), $data);

        return TransactionAcknowledgement::fromArray($response->unwrap());
    }

    /**
     * Submit a transaction by invoking a transaction code template (Mode 2).
     *
     * The server resolves $code to an active trancode in the current ledger, validates
     * $params against the trancode's params_schema, runs the entries template through
     * the expression engine, and writes the resulting balanced entries. Returns a
     * `TransactionAcknowledgement`; same async polling story as {@see self::create()}.
     *
     * @param string               $code   Trancode `code` (e.g. "BOOK_TRANSFER"). Must be active.
     * @param array<string, mixed> $params Parameter map matching the trancode's params_schema.
     * @param array<string, mixed> $extra  Wrapper fields — `description`, `effective_date`,
     *                                     `idempotency_key` are required by the server;
     *                                     `layer`, `journal_id`, `correlation_id`,
     *                                     `correlation_type`, `reference`, `metadata` optional.
     */
    public function createFromCode(string $code, array $params, array $extra = []): TransactionAcknowledgement
    {
        return $this->create(array_merge($extra, [
            'transaction_code' => $code,
            'transaction_code_params' => $params,
        ]));
    }

    /**
     * Reverse a transaction.
     *
     * @param array<string, mixed> $data Reversal data (reason, date required)
     */
    public function reverse(string $id, array $data): Transaction
    {
        $response = $this->http->post($this->basePath() . '/' . $id . '/reverse', $data);

        return Transaction::fromArray($response->unwrap());
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

        return BatchResponse::fromArray($response->unwrap());
    }
}
