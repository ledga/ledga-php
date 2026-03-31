<?php

declare(strict_types=1);

namespace Ledga\Api;

final readonly class Config
{
    public const DEFAULT_BASE_URL = 'https://ledga.io';
    public const DEFAULT_TIMEOUT = 30;

    public string $baseUrl;

    public function __construct(
        public string $apiKey,
        ?string $baseUrl = null,
        public int $timeout = self::DEFAULT_TIMEOUT,
    ) {
        $this->baseUrl = rtrim($baseUrl ?? self::DEFAULT_BASE_URL, '/');
    }

    public function getApiVersion(): string
    {
        return 'v1';
    }

    public function getApiBasePath(): string
    {
        return $this->baseUrl . '/api/' . $this->getApiVersion();
    }
}
