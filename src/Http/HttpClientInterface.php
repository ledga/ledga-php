<?php

declare(strict_types=1);

namespace Ledga\Api\Http;

interface HttpClientInterface
{
    /**
     * @param array<string, mixed> $query
     */
    public function get(string $path, array $query = []): Response;

    /**
     * @param array<string, mixed> $data
     */
    public function post(string $path, array $data = []): Response;

    /**
     * @param array<string, mixed> $data
     */
    public function put(string $path, array $data = []): Response;

    public function delete(string $path): Response;
}
