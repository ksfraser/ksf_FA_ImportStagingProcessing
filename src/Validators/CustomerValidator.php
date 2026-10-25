<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Validators;

use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationServiceInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationResult;

/**
 * Validates staged customer records against schema rules.
 *
 * Implements ValidationServiceInterface. Requires source and name; optionally
 * validates email format and name max length.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-008-001 validate staged records
 * @BABOK Related: NFR-ISU-006 validation errors
 * @BABOK Related: NFR-ISU-009 Strategy pattern
 */
class CustomerValidator implements ValidationServiceInterface
{
    private array $rules;

    /**
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 constructor
     */
    public function __construct()
    {
        $this->rules = [
            'source' => ['required' => true, 'type' => 'string'],
            'name' => ['required' => true, 'type' => 'string', 'max' => 255],
            'email' => ['required' => false, 'type' => 'email'],
            'phone' => ['required' => false, 'type' => 'string'],
        ];
    }

    /**
     * Validate a staged customer record against schema rules.
     *
     * @param array $record
     * @return ValidationResult
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 validate
     */
    public function validate(array $record): ValidationResult
    {
        $errors = [];
        $warnings = [];
        foreach ($this->rules as $field => $rule) {
            $value = $record[$field] ?? null;
            if ($rule['required'] ?? false) {
                if ($value === null || $value === '') {
                    $errors[] = sprintf("Field '%s' is required", $field);
                    continue;
                }
            }
            if ($value !== null && $value !== '') {
                switch ($rule['type'] ?? 'string') {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $warnings[] = sprintf("Field '%s' is not a valid email", $field);
                        }
                        break;
                }
                if (isset($rule['max']) && strlen((string)$value) > $rule['max']) {
                    $errors[] = sprintf("Field '%s' exceeds max length of %d", $field, $rule['max']);
                }
            }
        }
        if (empty($errors)) {
            return ValidationResult::valid($warnings);
        }
        return ValidationResult::invalid($errors, $warnings);
    }

    /**
     * @return array
     * @since 2.0.0
     * @BABOK Related: FR-ISU-008-001 getRules
     */
    public function getRules(): array
    {
        return $this->rules;
    }
}
