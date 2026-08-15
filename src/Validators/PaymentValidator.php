<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Validators;

use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationServiceInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationResult;

class PaymentValidator implements ValidationServiceInterface
{
    private array $rules;
    private array $validMethods;

    public function __construct()
    {
        $this->validMethods = [
            'credit_card', 'cash', 'gift_card', 'check', 'other',
        ];
        $this->rules = [
            'source' => ['required' => true, 'type' => 'string'],
            'amount' => ['required' => true, 'type' => 'numeric', 'min' => 0.01],
            'payment_date' => ['required' => false, 'type' => 'date'],
            'payment_method' => ['required' => false, 'type' => 'payment_method'],
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
                    case 'payment_method':
                        if (!in_array($value, $this->validMethods, true)) {
                            $warnings[] = sprintf("Field '%s' has unrecognized value '%s'", $field, $value);
                        }
                        break;
                }
            }
        }

        if (isset($record['fee']) && $record['fee'] !== '' && is_numeric($record['fee']) && (float)$record['fee'] < 0) {
            $errors[] = "Field 'fee' must be >= 0";
        }

        if (isset($record['net_amount'], $record['amount'], $record['fee'])
            && $record['net_amount'] !== '' && $record['amount'] !== '' && $record['fee'] !== '') {
            $expectedNet = (float)$record['amount'] - (float)$record['fee'];
            if (abs($expectedNet - (float)$record['net_amount']) > 0.01) {
                $warnings[] = sprintf(
                    "Field 'net_amount' (%.2f) does not equal amount (%.2f) - fee (%.2f)",
                    (float)$record['net_amount'],
                    (float)$record['amount'],
                    (float)$record['fee']
                );
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

    public function getValidMethods(): array
    {
        return $this->validMethods;
    }

    private function isValidDate(string $date): bool
    {
        $d = \DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}
