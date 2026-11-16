<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use Ledga\Api\Enums\TransactionStatus;
use Ledga\Api\Exceptions\LedgaException;

/**
 * What `POST /transactions` returns (both direct entries and trancode-driven posting):
 * a 202 for a new posting, and the same body with a 200 for a replay of its
 * `idempotency_key`. The transaction is written before the 202 returns; only its
 * integrity verification runs afterwards. Fetch it with `transactions->get($ack->id)`.
 */
final readonly class TransactionAcknowledgement implements ResourceInterface
{
    /**
     * @param TransactionStatus $status Always `Pending`, on a new posting and on every
     *                              replay. It is not the transaction's own status; read
     *                              that from `transactions->get($ack->id)`.
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
