<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

/**
 * Thrown when a staged record already exists for the given source + id.
 *
 * Supports idempotent staging: update-if-exists instead of insert.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-005 Duplicate detection
 * @BABOK Related: NFR-ISU-006 Custom exception hierarchy
 */
class DuplicateTransactionException extends StagingException
{
    /**
     * Build an exception for a duplicate transaction from a source.
     *
     * @param string $source        Source name
     * @param string $transactionId Staged transaction id
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 duplicate transaction detection
     */
    public static function forSource(string $source, string $transactionId): self
    {
        return new self(sprintf('Transaction %s already staged from source %s', $transactionId, $source));
    }

    /**
     * Build an exception for a duplicate payment.
     *
     * @param string $paymentId Staged payment id
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-002 duplicate payment detection
     */
    public static function forPayment(string $paymentId): self
    {
        return new self(sprintf('Payment %s has already been staged', $paymentId));
    }
}
