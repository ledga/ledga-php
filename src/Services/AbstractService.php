<?php

declare(strict_types=1);

namespace Ledga\Api\Services;

use Ledga\Api\Http\HttpClientInterface;
use Ledga\Api\Pagination\CursorPaginator;
use Ledga\Api\Pagination\PaginatedResponse;
use Ledga\Api\Resources\ResourceInterface;

/**
 * @template T of ResourceInterface
 */
abstract class AbstractService
{
    public function __construct(
        protected readonly HttpClientInterface $http,
    ) {
    }

    /**
     * @return class-string<T>
     */
    abstract protected function resourceClass(): string;

    abstract protected function basePath(): string;

    /**
     * @param array<string, mixed> $params
     * @return PaginatedResponse<T>
     */
    protected function listRequest(string $path, array $params = []): PaginatedResponse
    {
        $response = $this->http->get($path, $params);

        return new PaginatedResponse(
            $response->data,
            $this->resourceClass(),
            $this->http,
            $path,
            $params,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return CursorPaginator<T>
     */
    protected function allRequest(string $path, array $params = []): CursorPaginator
    {
        return new CursorPaginator(
            $this->http,
            $path,
            $params,
            $this->resourceClass(),
        );
    }

    /**
     * @return T
     */
    protected function getRequest(string $path): object
    {
        $response = $this->http->get($path);
        $class = $this->resourceClass();

        return $class::fromArray($response->data);
    }

    /**
     * @param array<string, mixed> $data
     * @return T
     */
    protected function createRequest(string $path, array $data): object
    {
        $response = $this->http->post($path, $data);
        $class = $this->resourceClass();

        return $class::fromArray($response->data);
    }

    /**
     * @param array<string, mixed> $data
     * @return T
     */
    protected function updateRequest(string $path, array $data): object
    {
        $response = $this->http->put($path, $data);
        $class = $this->resourceClass();

        return $class::fromArray($response->data);
    }

    protected function deleteRequest(string $path): void
    {
        $this->http->delete($path);
    }
}
