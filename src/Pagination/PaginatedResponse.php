<?php

declare(strict_types=1);

namespace Ledga\Api\Pagination;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Resources\ResourceInterface;

/**
 * @template T of ResourceInterface
 */
final class PaginatedResponse
{
    /** @var T[] */
    public readonly array $data;

    public readonly ?string $nextCursor;

    public readonly ?string $prevCursor;

    public readonly int $perPage;

    /**
     * @param array<string, mixed> $response
     * @param class-string<T> $resourceClass
     * @param array<string, mixed> $originalParams
     */
    public function __construct(
        array $response,
        private readonly string $resourceClass,
        private readonly HttpClientInterface $http,
        private readonly string $path,
        private readonly array $originalParams = [],
    ) {
        $items = [];
        $dataArray = $response['data'] ?? [];
        foreach ($dataArray as $item) {
            $items[] = $resourceClass::fromArray($item);
        }
        $this->data = $items;

        $pagination = $response['meta']['pagination'] ?? [];
        $this->nextCursor = $pagination['next_cursor'] ?? null;
        $this->prevCursor = $pagination['previous_cursor'] ?? null;
        $this->perPage = $pagination['limit'] ?? 25;
    }

    public function hasMore(): bool
    {
        return $this->nextCursor !== null;
    }

    public function hasPrevious(): bool
    {
        return $this->prevCursor !== null;
    }

    /**
     * @return self<T>|null
     */
    public function nextPage(): ?self
    {
        if (!$this->hasMore()) {
            return null;
        }

        $params = array_merge($this->originalParams, ['cursor' => $this->nextCursor]);
        $response = $this->http->get($this->path, $params);

        return new self(
            $response->data,
            $this->resourceClass,
            $this->http,
            $this->path,
            $params,
        );
    }

    /**
     * @return self<T>|null
     */
    public function previousPage(): ?self
    {
        if (!$this->hasPrevious()) {
            return null;
        }

        $params = array_merge($this->originalParams, ['cursor' => $this->prevCursor]);
        $response = $this->http->get($this->path, $params);

        return new self(
            $response->data,
            $this->resourceClass,
            $this->http,
            $this->path,
            $params,
        );
    }
}
