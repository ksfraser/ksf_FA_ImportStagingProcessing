<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

/**
 * Thrown when staging pipeline processing fails (stage, match, invoice create).
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-012 Processing pipeline
 * @BABOK Related: NFR-ISU-006 Custom exception hierarchy
 */
class ProcessingException extends StagingException
{
    /**
     * Build an exception for a failed staging operation.
     *
     * @param int    $recordId Staged record id
     * @param string $reason   Failure reason
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 staging failure
     */
    public static function stageFailed(int $recordId, string $reason): self
    {
        return new self(sprintf('Failed to stage record %d: %s', $recordId, $reason));
    }

    /**
     * Build an exception for a failed match lookup.
     *
     * @param int $recordId Staged record id
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-001 match failure
     */
    public static function matchFailed(int $recordId): self
    {
        return new self(sprintf('Failed to find match for record %d', $recordId));
    }

    /**
     * Build an exception for a failed FA invoice creation.
     *
     * @param int    $recordId Staged record id
     * @param string $reason   Failure reason
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 invoice creation failure
     */
    public static function invoiceCreationFailed(int $recordId, string $reason): self
    {
        return new self(sprintf('Failed to create FA invoice for record %d: %s', $recordId, $reason));
    }
}
