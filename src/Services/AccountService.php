<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\Account;
use Ledga\Api\Resources\AccountBalance;
use Ledga\Api\Resources\AccountEntry;
use Ledga\Api\Resources\BalanceHistory;

/**
 * @extends AbstractService<Account>
 */
final class AccountService extends AbstractService
{
    protected function resourceClass(): string
    {
        return Account::class;
    }

    protected function basePath(): string
    {
        return 'accounts';
    }

    /**
     * List accounts with manual pagination.
     *
     * @param array<string, mixed> $params Filter parameters (type, active, parent_id, search, tree, limit, cursor)
     * @return PaginatedResponse<Account>
     */
    public function list(array $params = []): PaginatedResponse
    {
        return $this->listRequest($this->basePath(), $params);
    }

    /**
     * Iterate through all accounts with auto-pagination.
     *
     * @param array<string, mixed> $params Filter parameters
     * @return CursorPaginator<Account>
     */
    public function all(array $params = []): CursorPaginator
    {
        return $this->allRequest($this->basePath(), $params);
    }

    /**
     * Get a specific account.
     */
    public function get(string $id): Account
    {
        return $this->getRequest($this->basePath() . '/' . $id);
    }

    /**
     * Create a new account.
     *
     * @param array<string, mixed> $data Account data (code, name, type required)
     */
    public function create(array $data): Account
    {
        return $this->createRequest($this->basePath(), $data);
    }

    /**
     * Update an account.
     *
     * @param array<string, mixed> $data Account data to update
     */
    public function update(string $id, array $data): Account
    {
        return $this->updateRequest($this->basePath() . '/' . $id, $data);
    }

    /**
     * Delete an account.
     */
    public function delete(string $id): void
    {
        $this->deleteRequest($this->basePath() . '/' . $id);
    }

    /**
     * Get account balance with layer details.
     *
     * @param array<string, mixed> $params Optional parameters (as_of_date, layer)
     */
    public function getBalance(string $id, array $params = []): AccountBalance
    {
        $response = $this->http->get($this->basePath() . '/' . $id . '/balance', $params);

        return AccountBalance::fromArray($response->data);
    }

    /**
     * Initialize default chart of accounts.
     *
     * @return array<string, mixed>
     */
    public function initializeDefaults(): array
    {
        $response = $this->http->post($this->basePath() . '/initialize-defaults');

        return $response->data;
    }

    /**
     * Get an account by its code.
     */
    public function getByCode(string $code): Account
    {
        return $this->getRequest($this->basePath() . '/code/' . $code);
    }

    /**
     * Get account balance by code with layer details.
     *
     * @param array<string, mixed> $params Optional parameters (as_of_date, layer)
     */
    public function getBalanceByCode(string $code, array $params = []): AccountBalance
    {
        $response = $this->http->get($this->basePath() . '/code/' . $code . '/balance', $params);

        return AccountBalance::fromArray($response->data);
    }

    /**
     * Get account balance history.
     *
     * @param array<string, mixed> $params Optional parameters (start_date, end_date)
     */
    public function getBalanceHistory(string $id, array $params = []): BalanceHistory
    {
        $response = $this->http->get($this->basePath() . '/' . $id . '/balance-history', $params);

        return BalanceHistory::fromArray($response->data);
    }

    /**
     * Get all entries for an account with rolling balance.
     *
     * Returns entries in chronological order by effective_date with a calculated
     * balance_after field showing the running balance after each entry.
     *
     * @param array<string, mixed> $params Optional parameters (start_date, end_date, layer, limit, cursor)
     * @return PaginatedResponse<AccountEntry>
     */
    public function getEntries(string $id, array $params = []): PaginatedResponse
    {
        $path = $this->basePath() . '/' . $id . '/entries';
        $response = $this->http->get($path, $params);

        /** @var PaginatedResponse<AccountEntry> */
        return new PaginatedResponse(
            $response->data,
            AccountEntry::class,
            $this->http,
            $path,
            $params,
        );
    }
}
