<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use DateTimeInterface;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingTransaction;

/**
 * Contract for transaction staging data access.
 *
 * Implementations provide CRUD operations for StagingTransaction records.
 *
 * @requirement FR-02 Transaction Staging
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
interface TransactionRepositoryInterface
{
    /**
     * Insert a new staging transaction.
     *
     * @param StagingTransaction $transaction The transaction to insert
     * @return int The auto-generated ID
     */
    public function insert(StagingTransaction $transaction): int;

    /**
     * Find a staging transaction by primary key.
     *
     * @param int $id The row ID
     * @return StagingTransaction|null null when not found
     */
    public function findById(int $id): ?StagingTransaction;

    /**
     * Find a staging transaction by source + source_transaction_id.
     *
     * @param string $source              The source system
     * @param string $sourceTransactionId The source-side transaction identifier
     * @return StagingTransaction|null null when not found
     */
    public function findBySource(string $source, string $sourceTransactionId): ?StagingTransaction;

    /**
     * Find staging transactions filtered by status and optionally source.
     *
     * @param string      $status The status to filter on
     * @param string|null $source Optional source filter
     * @return StagingTransaction[]
     */
    public function findByStatus(string $status, ?string $source = null): array;

    /**
     * Find staging transactions within a date range.
     *
     * @param DateTimeInterface $from   Start date (inclusive)
     * @param DateTimeInterface $to     End date (inclusive)
     * @param string|null       $source Optional source filter
     * @return StagingTransaction[]
     */
    public function findByDateRange(DateTimeInterface $from, DateTimeInterface $to, ?string $source = null): array;

    /**
     * Update the status and optional confidence/error of a staging transaction.
     *
     * @param int         $id         The row ID
     * @param string      $status     New status value
     * @param float|null  $confidence Optional match confidence
     * @param string|null $error      Optional error message
     */
    public function updateStatus(int $id, string $status, ?float $confidence = null, ?string $error = null): void;

    /**
     * Write the FA invoice/debtor reference onto a staging transaction.
     *
     * @param int         $id        The row ID
     * @param int         $invoiceNo FA invoice number
     * @param int|null    $debtorNo  FA debtor number
     */
    public function updateFaReference(int $id, int $invoiceNo, ?int $debtorNo = null): void;

    /**
     * Update a staging transaction by its source + source_transaction_id key.
     *
     * @param StagingTransaction $transaction The transaction with updated fields
     * @return bool True if a row was affected
     */
    public function updateBySource(StagingTransaction $transaction): bool;

    /**
     * Get transactions ready for processing (staged or validated).
     *
     * @param string|null $source Optional source filter
     * @param int         $limit  Maximum records to return
     * @return StagingTransaction[]
     */
    public function getQueueForProcessing(?string $source = null, int $limit = 100): array;

    /**
     * Count staging transactions grouped by status.
     *
     * @param string|null $source Optional source filter
     * @return array<string,int> Map of status => count
     */
    public function countByStatus(?string $source = null): array;
}
