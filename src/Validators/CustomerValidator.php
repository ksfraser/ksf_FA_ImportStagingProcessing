<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Validators;

use Ksfraser\ImportStaging\Contracts\ValidationServiceInterface;
use Ksfraser\ImportStaging\Contracts\ValidationResult;

class CustomerValidator implements ValidationServiceInterface
{
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            'source' => ['required' => true, 'type' => 'string'],
            'name' => ['required' => true, 'type' => 'string', 'max' => 255],
            'email' => ['required' => false, 'type' => 'email'],
            'phone' => ['required' => false, 'type' => 'string'],
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

    public function getRules(): array
    {
        return $this->rules;
    }
}
