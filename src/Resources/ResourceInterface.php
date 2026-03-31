<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

interface ResourceInterface
{
    /**
     * @param array<string, mixed> $data
     * @return static
     */
    public static function fromArray(array $data): static;
}
