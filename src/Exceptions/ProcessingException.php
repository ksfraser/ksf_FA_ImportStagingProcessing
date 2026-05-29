<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Exceptions;

class ProcessingException extends StagingException
{
    public static function stageFailed(int $recordId, string $reason): self
    {
        return new self(sprintf('Failed to stage record %d: %s', $recordId, $reason));
    }

    public static function matchFailed(int $recordId): self
    {
        return new self(sprintf('Failed to find match for record %d', $recordId));
    }

    public static function invoiceCreationFailed(int $recordId, string $reason): self
    {
        return new self(sprintf('Failed to create FA invoice for record %d: %s', $recordId, $reason));
    }
}
