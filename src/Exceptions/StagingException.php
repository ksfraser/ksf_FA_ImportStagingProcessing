<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Exceptions;

class StagingException extends \RuntimeException
{
    public static function notFound(int $id, string $type = 'record'): self
    {
        return new self(sprintf('%s with ID %d not found', ucfirst($type), $id));
    }

    public static function invalidSource(string $source): self
    {
        return new self(sprintf('Invalid source: %s', $source));
    }

    public static function duplicateTransaction(string $sourceTransactionId): self
    {
        return new self(sprintf('Duplicate transaction: %s already exists', $sourceTransactionId));
    }

    public static function mappingFailed(string $source, string $field): self
    {
        return new self(sprintf('Mapping failed for source %s, field %s', $source, $field));
    }

    public static function processingFailed(string $reason): self
    {
        return new self(sprintf('Processing failed: %s', $reason));
    }

    public static function validationFailed(array $errors): self
    {
        return new self('Validation failed: ' . implode('; ', $errors));
    }
}
