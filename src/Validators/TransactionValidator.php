<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Validators;

use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationServiceInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationResult;

class TransactionValidator implements ValidationServiceInterface
{
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            'source' => ['required' => true, 'type' => 'string'],
            'total_amount' => ['required' => true, 'type' => 'numeric', 'min' => 0],
            'transaction_date' => ['required' => false, 'type' => 'date'],
        ];
    }

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
