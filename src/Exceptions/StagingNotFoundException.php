<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Exceptions;

class StagingNotFoundException extends StagingException
{
    public static function byId(int $id, string $type = 'record'): self
    {
        return new self(sprintf('%s with ID %d not found', ucfirst($type), $id));
    }

    public static function bySource(string $source, string $sourceId): self
    {
        return new self(sprintf('Record not found for source %s with ID %s', $source, $sourceId));
    }
}
