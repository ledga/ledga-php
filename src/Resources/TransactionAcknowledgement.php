<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use Ledga\Api\Enums\TransactionStatus;

/**
 * 202-style acknowledgement returned by `POST /transactions` (both direct entries and
 * trancode-driven posting). The server has accepted the request but not yet committed
 * the entries — fetch the full transaction via `transactions->get($ack->id)` once the
 * status moves off `Pending`.
 */
final readonly class TransactionAcknowledgement implements ResourceInterface
{
    public function __construct(
        public string $id,
        public TransactionStatus $status,
        public string $idempotencyKey,
        public string $correlationId,
        public ?string $message,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            id: $data['id'],
            status: TransactionStatus::from($data['status']),
            idempotencyKey: $data['idempotency_key'],
            correlationId: $data['correlation_id'],
            message: $data['message'] ?? null,
        );
    }
}
