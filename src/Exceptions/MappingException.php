<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

class MappingException extends StagingException
{
    public static function fieldNotFound(string $source, string $field): self
    {
        return new self(sprintf('Field %s not found in mapping configuration for source %s', $field, $source));
    }

    public static function transformFailed(string $transform, string $value): self
    {
        return new self(sprintf('Transform %s failed for value: %s', $transform, $value));
    }
}
