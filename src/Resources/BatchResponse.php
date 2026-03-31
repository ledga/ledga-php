<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

/**
 * Represents the response from a batch transaction request.
 */
final readonly class BatchResponse implements ResourceInterface
{
    /**
     * @param BatchResult[] $results
     */
    public function __construct(
        public array $results,
        public int $total,
        public int $accepted,
        public int $rejected,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        $results = [];
        if (isset($data['results']) && is_array($data['results'])) {
            foreach ($data['results'] as $result) {
                $results[] = BatchResult::fromArray($result);
            }
        }

        return new self(
            results: $results,
            total: $data['summary']['total'] ?? count($results),
            accepted: $data['summary']['accepted'] ?? 0,
            rejected: $data['summary']['rejected'] ?? 0,
        );
    }

    public function hasRejections(): bool
    {
        return $this->rejected > 0;
    }

    public function allAccepted(): bool
    {
        return $this->rejected === 0 && $this->accepted === $this->total;
    }

    /**
     * @return BatchResult[]
     */
    public function getAccepted(): array
    {
        return array_filter($this->results, fn (BatchResult $r) => $r->isAccepted());
    }

    /**
     * @return BatchResult[]
     */
    public function getRejected(): array
    {
        return array_filter($this->results, fn (BatchResult $r) => $r->isRejected());
    }
}
