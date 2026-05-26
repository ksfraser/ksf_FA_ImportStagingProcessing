<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Contracts;

interface ProcessorInterface
{
    public function process(array $record, array $context = []): ProcessingResult;

    public function canProcess(array $record): bool;
}
