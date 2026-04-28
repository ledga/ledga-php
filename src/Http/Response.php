<?php

declare(strict_types=1);

namespace Ledga\Api\Http;

use Ledga\Api\Exceptions\LedgaException;

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
     * Strip the uniform `{success, data, ...}` envelope and return the inner `data` map.
     *
     * Behaviour:
     *  - `data` is an associative array → return it (the common single-resource case).
     *  - `data` is a list (including `[]`) → return the full body unchanged so list / paginated callers keep `meta`.
     *  - `data` key absent or non-array (null, scalar, etc.) → malformed response, throw `LedgaException`.
     *
     * @return array<string, mixed>
     * @throws LedgaException
     */
    public function unwrap(): array
    {
        if (!array_key_exists('data', $this->data)) {
            throw new LedgaException(
                'Malformed response: expected uniform envelope with `data` key, got keys: '
                . implode(', ', array_keys($this->data) ?: ['(none)']),
            );
        }

        $inner = $this->data['data'];

        if (!is_array($inner)) {
            throw new LedgaException(
                'Malformed response: expected `data` envelope to be an array, got ' . gettype($inner),
            );
        }

        if (array_is_list($inner)) {
            return $this->data;
        }

        /** @var array<string, mixed> $inner */
        return $inner;
    }
}
