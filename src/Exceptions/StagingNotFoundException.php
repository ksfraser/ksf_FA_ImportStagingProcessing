<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

/**
 * Thrown when a staged record is not found by id or source.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-007-001 staging_log lookup
 * @BABOK Related: NFR-ISU-006 Custom exception hierarchy
 */
class StagingNotFoundException extends StagingException
{
    /**
     * Build an exception for a record not found by id.
     *
     * @param int    $id    Record ID
     * @param string $type  Record type label
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-007-001 record not found by id
     */
    public static function byId(int $id, string $type = 'record'): self
    {
        return new self(sprintf('%s with ID %d not found', ucfirst($type), $id));
    }

    /**
     * Build an exception for a record not found by source + id.
     *
     * @param string $source  Source name
     * @param string $sourceId Source record id
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-007-001 record not found by source
     */
    public static function bySource(string $source, string $sourceId): self
    {
        return new self(sprintf('Record not found for source %s with ID %s', $source, $sourceId));
    }
}
