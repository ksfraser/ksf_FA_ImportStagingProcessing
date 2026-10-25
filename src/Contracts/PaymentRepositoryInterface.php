<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use DateTimeInterface;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingPayment;

/**
 * Contract for payment staging data access.
 *
 * Implementations provide CRUD operations for StagingPayment records.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-003-001 payment staging
 * @BABOK Related: FR-ISU-005-002 duplicate detection (findBySource)
 * @BABOK Related: FR-ISU-013-001 reconciliation input
 * @BABOK Related: NFR-ISU-009 Repository pattern
 */
interface PaymentRepositoryInterface
{
    /**
     * Insert a new staging payment.
     *
     * @param StagingPayment $payment The payment to insert
     * @return int The auto-generated ID
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 insert
     */
    public function insert(StagingPayment $payment): int;

    /**
     * Find a staging payment by primary key.
     *
     * @param int $id The row ID
     * @return StagingPayment|null null when not found
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 findById
     */
    public function findById(int $id): ?StagingPayment;

    /**
     * Find a staging payment by source + source_payment_id.
     *
     * @param string $source          The source system
     * @param string $sourcePaymentId The source-side payment identifier
     * @return StagingPayment|null null when not found
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-002 findBySource
     */
    public function findBySource(string $source, string $sourcePaymentId): ?StagingPayment;

    /**
     * Find staging payments filtered by status and optionally source.
     *
     * @param string      $status The status to filter on
     * @param string|null $source Optional source filter
     * @return StagingPayment[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 findByStatus
     */
    public function findByStatus(string $status, ?string $source = null): array;

    /**
     * Find staging payments within a date range.
     *
     * @param DateTimeInterface $from   Start date (inclusive)
     * @param DateTimeInterface $to     End date (inclusive)
     * @param string|null       $source Optional source filter
     * @return StagingPayment[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 findByDateRange
     */
    public function findByDateRange(DateTimeInterface $from, DateTimeInterface $to, ?string $source = null): array;

    /**
     * Find all staging payments linked to a staging transaction.
     *
     * @param int $stagingTransactionId FK to staging_transactions
     * @return StagingPayment[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 findByTransaction
     */
    public function findByTransaction(int $stagingTransactionId): array;

    /**
     * Update the status and optional confidence/error of a staging payment.
     *
     * @param int         $id         The row ID
     * @param string      $status     New status value
     * @param float|null  $confidence Optional match confidence
     * @param string|null $error      Optional error message
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updateStatus
     */
    public function updateStatus(int $id, string $status, ?float $confidence = null, ?string $error = null): void;

    /**
     * Write the FA payment reference onto a staging payment.
     *
     * @param int         $id          The row ID
     * @param int         $faTransType FA transaction type
     * @param int         $faTransNo   FA transaction number
     * @param string|null $faBankAccount Optional bank account
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 updateFaReference
     */
    public function updateFaReference(int $id, int $faTransType, int $faTransNo, ?string $faBankAccount = null): void;

    /**
     * Update a staging payment by its source + source_payment_id key.
     *
     * @param StagingPayment $payment The payment with updated fields
     * @return bool True if a row was affected
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 updateBySource
     */
    public function updateBySource(StagingPayment $payment): bool;

    /**
     * Get payments ready for reconciliation (staged, validated, or matched).
     *
     * @param string|null $source Optional source filter
     * @param int         $limit  Maximum records to return
     * @return StagingPayment[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 getQueueForReconciliation
     */
    public function getQueueForReconciliation(?string $source = null, int $limit = 100): array;

    /**
     * Count staging payments grouped by status.
     *
     * @param string|null $source Optional source filter
     * @return array<string,int> Map of status => count
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 countByStatus
     */
    public function countByStatus(?string $source = null): array;
}
