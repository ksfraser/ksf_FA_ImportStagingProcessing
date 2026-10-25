<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

/**
 * Thrown when field mapping configuration is missing or a transform fails.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-006 Field mapping configuration
 * @BABOK Related: NFR-ISU-006 Custom exception hierarchy
 */
class MappingException extends StagingException
{
    /**
     * Build an exception for a missing mapping field.
     *
     * @param string $source Source name
     * @param string $field  Field name
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-006-001 field not found in mapping
     */
    public static function fieldNotFound(string $source, string $field): self
    {
        return new self(sprintf('Field %s not found in mapping configuration for source %s', $field, $source));
    }

    /**
     * Build an exception for a failed field transform.
     *
     * @param string $transform Transform name
     * @param string $value     Value being transformed
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-006-002 field transform
     */
    public static function transformFailed(string $transform, string $value): self
    {
        return new self(sprintf('Transform %s failed for value: %s', $transform, $value));
    }
}
