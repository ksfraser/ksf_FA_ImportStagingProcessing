<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingPaymentMatch;

/**
 * Contract for payment match (reconciliation audit) data access.
 *
 * Implementations provide CRUD operations for StagingPaymentMatch records.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-013-001 payment match records
 * @BABOK Related: FR-ISU-014-001 match status lifecycle
 * @BABOK Related: NFR-ISU-009 Repository pattern
 */
interface PaymentMatchRepositoryInterface
{
    /**
     * Insert a new payment match record.
     *
     * @param StagingPaymentMatch $match The match to insert
     * @return int The auto-generated ID
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 insert
     */
    public function insert(StagingPaymentMatch $match): int;

    /**
     * Find a payment match by primary key.
     *
     * @param int $id The row ID
     * @return StagingPaymentMatch|null null when not found
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 findById
     */
    public function findById(int $id): ?StagingPaymentMatch;

    /**
     * Find all match records for a staging payment (newest first).
     *
     * @param int $stagingPaymentId FK to staging_payments
     * @return StagingPaymentMatch[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 findByPaymentId
     */
    public function findByPaymentId(int $stagingPaymentId): array;

    /**
     * Find match records filtered by match_status.
     *
     * @param string $matchStatus The status to filter on
     * @return StagingPaymentMatch[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-014-001 findByStatus
     */
    public function findByStatus(string $matchStatus): array;

    /**
     * Update the match status and optional notes.
     *
     * @param int         $id          The row ID
     * @param string      $matchStatus New match status
     * @param string|null $notes       Optional notes
     * @since 2.0.0
     * @BABOK Related: FR-ISU-014-001 updateStatus
     */
    public function updateStatus(int $id, string $matchStatus, ?string $notes = null): void;

    /**
     * Get the most recent match record for a staging payment.
     *
     * @param int $stagingPaymentId FK to staging_payments
     * @return StagingPaymentMatch|null null when no matches exist
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-001 getLatestByPaymentId
     */
    public function getLatestByPaymentId(int $stagingPaymentId): ?StagingPaymentMatch;
}
