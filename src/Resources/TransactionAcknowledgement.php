<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Exceptions\LedgaException;

/**
 * 202-style acknowledgement returned by `POST /transactions` (both direct entries and
 * trancode-driven posting). The server has accepted the request but not yet committed
 * the entries — fetch the full transaction via `transactions->get($ack->id)` once the
 * status moves off `Pending`.
 */
final readonly class TransactionAcknowledgement implements ResourceInterface
{
    /**
     * @param string $correlationId The transaction's correlation handle: the `correlation_id`
     *                              sent; else, when it was linked with
     *                              `correlation_source_id`, the source's handle; else the
     *                              transaction's own id. Never null. Reads of the
     *                              transaction return the same value, and
     *                              {@see \Ledga\Api\Services\TransactionService::byCorrelation()}
     *                              finds the transaction by it.
     */
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
     * @throws LedgaException
     */
    public static function fromArray(array $data): static
    {
        foreach (['id', 'status', 'idempotency_key', 'correlation_id'] as $required) {
            if (!isset($data[$required]) || !is_string($data[$required])) {
                throw new LedgaException(
                    "Malformed transaction acknowledgement: missing or non-string '{$required}'.",
                );
            }
        }

        return new self(
            id: $data['id'],
            status: TransactionStatus::from($data['status']),
            idempotencyKey: $data['idempotency_key'],
            correlationId: $data['correlation_id'],
            message: $data['message'] ?? null,
        );
    }
}
