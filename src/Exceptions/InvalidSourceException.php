<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

/**
 * Thrown when a source name is unknown or empty.
 *
 * Guard for every public stage* entry point so an unknown source never
 * reaches a DAO.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-005-001 source validation
 * @BABOK Related: NFR-ISU-006 Custom exception hierarchy
 */
class InvalidSourceException extends StagingException
{
    /**
     * Build an exception for an unknown source.
     *
     * @param string $source Source name
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 unknown source rejection
     */
    public static function unknownSource(string $source): self
    {
        return new self(sprintf('Unknown source: %s. Valid sources: woocommerce, square_api, square_csv, paypal, bank', $source));
    }

    /**
     * Build an exception for an empty source.
     *
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 empty source rejection
     */
    public static function emptySource(): self
    {
        return new self('Source must not be empty');
    }
}
