<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

final readonly class AccountBalance implements ResourceInterface
{
    public function __construct(
        public string $settled,
        public string $pending,
        public string $encumbered,
        public string $available,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            settled: $data['settled'] ?? '0.00',
            pending: $data['pending'] ?? '0.00',
            encumbered: $data['encumbered'] ?? '0.00',
            available: $data['available'] ?? '0.00',
        );
    }
}
