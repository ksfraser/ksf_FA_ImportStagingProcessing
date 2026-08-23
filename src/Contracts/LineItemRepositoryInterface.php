<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingLineItem;

/**
 * Contract for line-item staging data access.
 *
 * Implementations provide CRUD operations for StagingLineItem records.
 *
 * @requirement FR-06 Line Item Staging
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
interface LineItemRepositoryInterface
{
    /**
     * Insert a new staging line item.
     *
     * @param StagingLineItem $item The line item to insert
     * @return int The auto-generated ID
     */
    public function insert(StagingLineItem $item): int;

    /**
     * Find all line items belonging to a staging transaction.
     *
     * @param int $transactionId FK to staging_transactions.id
     * @return StagingLineItem[]
     */
    public function findByTransactionId(int $transactionId): array;

    /**
     * Find line items by source and optional source_id.
     *
     * @param string      $source   The source system
     * @param string|null $sourceId Optional source-side line item ID
     * @return StagingLineItem[]
     */
    public function findBySource(string $source, ?string $sourceId = null): array;

    /**
     * Find line items filtered by status and optionally source.
     *
     * @param string      $status The status to filter on
     * @param string|null $source Optional source filter
     * @return StagingLineItem[]
     */
    public function findByStatus(string $status, ?string $source = null): array;

    /**
     * Update the status of a staging line item.
     *
     * @param int         $id     The row ID
     * @param string      $status New status value
     * @param string|null $error  Optional error message
     */
    public function updateStatus(int $id, string $status, ?string $error = null): void;

    /**
     * Update a line item by its source + source_id key.
     *
     * @param StagingLineItem $item The line item with updated fields
     */
    public function updateBySource(StagingLineItem $item): void;

    /**
     * Delete all line items (and their attributes) for a staging transaction.
     *
     * @param int $transactionId FK to staging_transactions.id
     */
    public function deleteByTransactionId(int $transactionId): void;
}
