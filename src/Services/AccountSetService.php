<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Enums\AccountSetMemberType;
use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\Account;
use Ledga\Api\Resources\AccountSet;
use Ledga\Api\Resources\AccountSetMember;

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

    /**
     * Add an account or a nested account set as a member of this set.
     */
    public function addMember(string $id, AccountSetMemberType $memberType, string $memberId): AccountSetMember
    {
        $response = $this->http->post(
            $this->basePath() . '/' . $id . '/members',
            ['member_type' => $memberType->value, 'member_id' => $memberId],
        );

        return AccountSetMember::fromArray($response->unwrap());
    }

    /**
     * Remove an account or a nested account set from this set.
     */
    public function removeMember(string $id, AccountSetMemberType $memberType, string $memberId): AccountSetMember
    {
        $response = $this->http->delete(
            $this->basePath() . '/' . $id . '/members',
            ['member_type' => $memberType->value, 'member_id' => $memberId],
        );

        return AccountSetMember::fromArray($response->unwrap());
    }

    /**
     * Get every account in this set, recursing through nested sets.
     *
     * The API returns a flat, unpaginated list.
     *
     * @return list<Account>
     */
    public function getAccounts(string $id): array
    {
        $response = $this->http->get($this->basePath() . '/' . $id . '/accounts');

        /** @var list<array<string, mixed>> $items */
        $items = $response->unwrap()['data'];

        return array_map(static fn (array $item): Account => Account::fromArray($item), $items);
    }
}
