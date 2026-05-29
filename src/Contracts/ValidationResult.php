<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Contracts;

class ValidationResult
{
    private bool $success;
    private array $errors;
    private array $warnings;

    public function __construct(bool $success, array $errors = [], array $warnings = [])
    {
        $this->success = $success;
        $this->errors = $errors;
        $this->warnings = $warnings;
    }

    public function isSuccess(): bool { return $this->success; }
    public function getErrors(): array { return $this->errors; }
    public function getWarnings(): array { return $this->warnings; }
    public function hasErrors(): bool { return !empty($this->errors); }
    public function hasWarnings(): bool { return !empty($this->warnings); }

    public static function valid(array $warnings = []): self
    {
        return new self(true, [], $warnings);
    }

    public static function invalid(array $errors, array $warnings = []): self
    {
        return new self(false, $errors, $warnings);
    }
}
