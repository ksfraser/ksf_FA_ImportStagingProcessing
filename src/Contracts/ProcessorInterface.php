<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

/**
 * Contract for a processing strategy that handles a staged record.
 *
 * Implementations encapsulate a single processing step (e.g. invoice
 * creation, payment allocation) behind a uniform process/canProcess API.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-012-001 processing pipeline
 * @BABOK Related: NFR-ISU-009 Strategy pattern
 */
interface ProcessorInterface
{
    /**
     * Process a staged record into a ProcessingResult.
     *
     * @param array  $record  Staged record data
     * @param array  $context Optional processing context
     * @return ProcessingResult
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 process
     */
    public function process(array $record, array $context = []): ProcessingResult;

    /**
     * Determine whether this processor can handle a record.
     *
     * @param array $record Staged record data
     * @return bool
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 canProcess
     */
    public function canProcess(array $record): bool;
}
