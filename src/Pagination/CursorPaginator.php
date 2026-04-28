<?php

declare(strict_types=1);

namespace Ledga\Api\Pagination;

use Generator;
use IteratorAggregate;
use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Resources\ResourceInterface;

/**
 * Auto-paginating iterator that yields all items across pages.
 *
 * @template T of ResourceInterface
 * @implements IteratorAggregate<int, T>
 */
final class CursorPaginator implements IteratorAggregate
{
    /**
     * @param class-string<T> $resourceClass
     * @param array<string, mixed> $params
     */
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly string $path,
        private readonly array $params,
        private readonly string $resourceClass,
    ) {
    }

    /**
     * @return Generator<int, T>
     */
    public function getIterator(): Generator
    {
        $cursor = null;
        $index = 0;

        do {
            $params = $this->params;
            if ($cursor !== null) {
                $params['cursor'] = $cursor;
            }

            $response = $this->http->get($this->path, $params);
            $data = $response->data;

            $items = $data['data'] ?? [];
            foreach ($items as $item) {
                yield $index++ => ($this->resourceClass)::fromArray($item);
            }

            $cursor = $data['meta']['pagination']['next_cursor'] ?? null;
        } while ($cursor !== null);
    }

    /**
     * Collect all items into an array.
     *
     * @return T[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this, false);
    }
}
