<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

/**
 * Result object for validation operations.
 *
 * Carries success flag, error messages, and warnings. Used by validators
 * and the staging pipeline to gate record acceptance.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-008-001 validation result
 * @BABOK Related: NFR-ISU-009 Value Object pattern
 */
class ValidationResult
{
    /**
     * @param bool $success
     * @param array $errors
     * @param array $warnings
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 constructor
     */
    public function __construct(bool $success, array $errors = [], array $warnings = [])
    {
        $this->success = $success;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    /**
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 isSuccess
     */
    public function isSuccess(): bool { return $this->success; }
    /**
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 getErrors
     */
    public function getErrors(): array { return $this->errors; }
    /**
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 getWarnings
     */
    public function getWarnings(): array { return $this->warnings; }
    /**
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 hasErrors
     */
    public function hasErrors(): bool { return !empty($this->errors); }
    /**
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 hasWarnings
     */
    public function hasWarnings(): bool { return !empty($this->warnings); }

    /**
     * Create a valid result with optional warnings.
     *
     * @param array $warnings
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 valid
     */
    public static function valid(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    /**
     * Create an invalid result with errors.
     *
     * @param array $errors
     * @param array $warnings
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 invalid
     */
    public static function invalid(array $errors, array $warnings = []): self
    {
        return new self(false, $errors, $warnings);
    }
}
