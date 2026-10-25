<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

/**
 * Base exception for all Import Staging Processing errors.
 *
 * Factory methods produce domain-specific exception instances for the staging
 * pipeline (validation, duplicate detection, mapping, processing).
 *
 * @since 2.0.0
 * @BABOK Related: NFR-ISU-006 Custom exception hierarchy
 * @BABOK Related: FR-ISU-005 Duplicate detection
 * @BABOK Related: FR-ISU-006 Field mapping
 * @BABOK Related: FR-ISU-008 Validation
 * @BABOK Related: FR-ISU-012 Processing
 * @BABOK Related: FR-ISU-016 Audit logging
 */
class StagingException extends \RuntimeException
{
    /**
     * Build a "not found" exception for a staged record.
     *
     * @param int    $id    Record ID
     * @param string $type  Record type label
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-007-001 staging_log lookup
     */
    public static function notFound(int $id, string $type = 'record'): self
    {
        return new self(sprintf('%s with ID %d not found', ucfirst($type), $id));
    }

    /**
     * Build an "invalid source" exception.
     *
     * @param string $source Source name
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 source validation
     */
    public static function invalidSource(string $source): self
    {
        return new self(sprintf('Invalid source: %s', $source));
    }

    /**
     * Build a "duplicate transaction" exception.
     *
     * @param string $sourceTransactionId Staged transaction id
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 duplicate detection
     */
    public static function duplicateTransaction(string $sourceTransactionId): self
    {
        return new self(sprintf('Duplicate transaction: %s already exists', $sourceTransactionId));
    }

    /**
     * Build a "mapping failed" exception.
     *
     * @param string $source  Source name
     * @param string $field   Field name
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-006-001 field mapping
     */
    public static function mappingFailed(string $source, string $field): self
    {
        return new self(sprintf('Mapping failed for source %s, field %s', $source, $field));
    }

    /**
     * Build a "processing failed" exception.
     *
     * @param string $reason Failure reason
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 processing
     */
    public static function processingFailed(string $reason): self
    {
        return new self(sprintf('Processing failed: %s', $reason));
    }

    /**
     * Build a "validation failed" exception.
     *
     * @param array $errors Validation error messages
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 validation
     */
    public static function validationFailed(array $errors): self
    {
        return new self('Validation failed: ' . implode('; ', $errors));
    }
}
