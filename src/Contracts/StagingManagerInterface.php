<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingTransaction;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingPayment;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingLineItem;

/**
 * Contract for the unified staging manager service.
 *
 * Defines the CRUD and query operations for staging customers, transactions,
 * payments, and line items across all import sources.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-001 staging_customers
 * @BABOK Related: FR-ISU-002 staging_transactions
 * @BABOK Related: FR-ISU-003 staging_payments
 * @BABOK Related: FR-ISU-004 staging_line_items
 * @BABOK Related: FR-ISU-005 duplicate detection
 * @BABOK Related: FR-ISU-008 validation
 * @BABOK Related: FR-ISU-009 matching
 * @BABOK Related: FR-ISU-012 processing
 * @BABOK Related: NFR-ISU-009 Repository pattern
 */
interface StagingManagerInterface
{
    /**
     * Stage a new customer record.
     *
     * @param array  $data   Customer data
     * @param string $source Source system identifier
     * @return StagingCustomer
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 stageCustomer
     */
    public function stageCustomer(array $data, string $source): StagingCustomer;

    /**
     * Upsert a customer by source + source_customer_id.
     *
     * @param array  $data   Customer data
     * @param string $source Source system identifier
     * @return StagingCustomer
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 stageOrUpdateCustomer
     */
    public function stageOrUpdateCustomer(array $data, string $source): StagingCustomer;

    /**
     * Stage a new transaction record.
     *
     * @param array  $data   Transaction data
     * @param string $source Source system identifier
     * @return StagingTransaction
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 stageTransaction
     */
    public function stageTransaction(array $data, string $source): StagingTransaction;

    /**
     * Upsert a transaction by source + source_transaction_id.
     *
     * @param array  $data   Transaction data
     * @param string $source Source system identifier
     * @return StagingTransaction
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 stageOrUpdateTransaction
     */
    public function stageOrUpdateTransaction(array $data, string $source): StagingTransaction;

    /**
     * Get staged customers filtered by status and source.
     *
     * @param array $filters Filter criteria (status, source)
     * @return StagingCustomer[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 getStagedCustomers
     */
    public function getStagedCustomers(array $filters = []): array;

    /**
     * Get staged transactions filtered by status and source.
     *
     * @param array $filters Filter criteria (status, source)
     * @return StagingTransaction[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 getStagedTransactions
     */
    public function getStagedTransactions(array $filters = []): array;

    /**
     * Update the status (and optional error log) of a staged transaction.
     *
     * @param int         $id     Transaction row ID
     * @param string      $status New status value
     * @param string|null $error  Optional error message
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updateStatus
     */
    public function updateStatus(int $id, string $status, ?string $error = null): void;

    /**
     * Process the staging queue for a source.
     *
     * @param string|null $source Optional source filter
     * @return ProcessingResult
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 processQueue
     */
    public function processQueue(?string $source = null): ProcessingResult;

    /**
     * Stage a line item belonging to a staging transaction.
     *
     * Core columns are persisted to staging_line_items; extra source-specific
     * fields in the 'attributes' key go to staging_line_item_attributes.
     *
     * @param array  $data   Line item data
     * @param string $source Source system identifier
     * @return StagingLineItem
     * @since 2.0.0
     * @BABOK Related: FR-ISU-004-001 stageLineItem
     */
    public function stageLineItem(array $data, string $source): StagingLineItem;

    /**
     * Upsert a line item by source + source_id.
     *
     * @param array  $data   Line item data
     * @param string $source Source system identifier
     * @return StagingLineItem
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 stageOrUpdateLineItem
     */
    public function stageOrUpdateLineItem(array $data, string $source): StagingLineItem;

    /**
     * Get all line items for a staging transaction.
     *
     * @param int $stagingTransactionId FK to staging_transactions
     * @return StagingLineItem[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-004-002 getLineItemsByTransaction
     */
    public function getLineItemsByTransaction(int $stagingTransactionId): array;

    /**
     * Stage a new payment record.
     *
     * @param array  $data                   Payment data
     * @param string $source                 Source system identifier
     * @param int|null $stagingTransactionId Optional parent transaction ID
     * @return StagingPayment
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 stagePayment
     */
    public function stagePayment(array $data, string $source, ?int $stagingTransactionId = null): StagingPayment;

    /**
     * Upsert a payment by source + source_payment_id.
     *
     * @param array  $data                   Payment data
     * @param string $source                 Source system identifier
     * @param int|null $stagingTransactionId Optional parent transaction ID
     * @return StagingPayment
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 stageOrUpdatePayment
     */
    public function stageOrUpdatePayment(array $data, string $source, ?int $stagingTransactionId = null): StagingPayment;

    /**
     * Get line items by source (and optional source_id).
     *
     * @param string      $source   Source system identifier
     * @param string|null $sourceId Optional source-side line item ID
     * @return StagingLineItem[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-004-001 getLineItemsBySource
     */
    public function getLineItemsBySource(string $source, ?string $sourceId = null): array;

    /**
     * Delete all line items for a staging transaction.
     *
     * @param int $stagingTransactionId FK to staging_transactions
     * @since 2.0.0
     * @BABOK Related: FR-ISU-004-001 deleteLineItemsByTransaction
     */
    public function deleteLineItemsByTransaction(int $stagingTransactionId): void;
}
