<?php

declare(strict_types=1);

namespace Ledga\Api\Http;

final readonly class Response
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string[]> $headers
     */
    public function __construct(
        public int $statusCode,
        public array $data,
        public array $headers = [],
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function getHeader(string $name): ?string
    {
        $normalized = strtolower($name);
        foreach ($this->headers as $key => $values) {
            if (strtolower($key) === $normalized) {
                return $values[0] ?? null;
            }
        }
        return null;
    }

    /**
     * Strip the uniform `{success, data, message?, meta?}` envelope used by every
     * single-resource endpoint. Falls through unchanged for already-flat or
     * list-shaped payloads (e.g. legacy fixtures, tests).
     *
     * @return array<string, mixed>
     */
    public function unwrap(): array
    {
        if (isset($this->data['data']) && is_array($this->data['data']) && !array_is_list($this->data['data'])) {
            /** @var array<string, mixed> $payload */
            $payload = $this->data['data'];
            return $payload;
        }

        return $this->data;
    }
}
