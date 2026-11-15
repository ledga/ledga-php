<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

/**
 * A transaction's status. `Transaction::$status` is one of Pending, Posted, Void,
 * Reversed or Draft.
 *
 * `TransactionAcknowledgement::$status` is always Pending, on a new posting and on
 * every replay of its idempotency key. Verified and Failed are kept so that a replay
 * from an earlier API version, which could carry the transaction's integrity
 * verification status, still parses.
 */
enum TransactionStatus: string
{
    case Pending = 'pending';
    case Posted = 'posted';
    case Void = 'void';
    /** Sent only by an earlier API version's replay: verification failed. */
    case Failed = 'failed';
    case Reversed = 'reversed';
    case Draft = 'draft';
    /** Sent only by an earlier API version's replay: verification passed. */
    case Verified = 'verified';
}
