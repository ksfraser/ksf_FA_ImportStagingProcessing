<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Validators;

use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationServiceInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationResult;

/**
 * Validates staged transaction records against schema rules.
 *
 * Implements ValidationServiceInterface. Requires source and total_amount;
 * optionally validates transaction_date format.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-008-001 validate staged records
 * @BABOK Related: NFR-ISU-006 validation errors
 * @BABOK Related: NFR-ISU-009 Strategy pattern
 */
class TransactionValidator implements ValidationServiceInterface
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
            'total_amount' => ['required' => true, 'type' => 'numeric', 'min' => 0],
            'transaction_date' => ['required' => false, 'type' => 'date'],
        ];
    }

    /**
     * Validate a staged transaction record against schema rules.
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
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[] = sprintf("Field '%s' must be numeric", $field);
                        }
                        if (isset($rule['min']) && (float)$value < $rule['min']) {
                            $errors[] = sprintf("Field '%s' must be >= %s", $field, $rule['min']);
                        }
                        break;
                    case 'date':
                        if (!$this->isValidDate((string)$value)) {
                            $errors[] = sprintf("Field '%s' must be a valid date (Y-m-d format)", $field);
                        }
                        break;
                }
            }
        }
        if (empty($errors)) {
            return ValidationResult::valid();
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

    private function isValidDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
