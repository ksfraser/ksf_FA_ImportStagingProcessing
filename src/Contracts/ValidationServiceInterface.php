<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Contracts;

interface ValidationServiceInterface
{
    public function validate(array $record): ValidationResult;

    public function getRules(): array;
}
