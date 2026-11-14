<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

/**
 * A transaction's status. `Transaction::$status` is one of Pending, Posted, Void,
 * Reversed or Draft.
 *
 * `TransactionAcknowledgement::$status` is Pending for a new submission. For a
 * replayed idempotency key the API may send the transaction's integrity
 * verification status instead, which can also be Verified or Failed.
 */
enum TransactionStatus: string
{
    case Pending = 'pending';
    case Posted = 'posted';
    case Void = 'void';
    /** Acknowledgement of a replay only: the transaction failed integrity verification. */
    case Failed = 'failed';
    case Reversed = 'reversed';
    case Draft = 'draft';
    /** Acknowledgement of a replay only: the transaction passed integrity verification. */
    case Verified = 'verified';
}
