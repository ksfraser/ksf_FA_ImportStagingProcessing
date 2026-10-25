<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

/**
 * Contract for validating staged records against schema rules.
 *
 * Implementations return a ValidationResult indicating success or failure
 * with error messages.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-008-001 validation
 * @BABOK Related: NFR-ISU-009 Strategy pattern
 */
interface ValidationServiceInterface
{
    /**
     * Validate a staged record against schema rules.
     *
     * @param array $record Record data to validate
     * @return ValidationResult
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 validate
     */
    public function validate(array $record): ValidationResult;

    /**
     * Get the validation rules applicable to this service.
     *
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 getRules
     */
    public function getRules(): array;
}
