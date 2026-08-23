<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingPaymentMatch;

/**
 * Contract for payment match (reconciliation audit) data access.
 *
 * Implementations provide CRUD operations for StagingPaymentMatch records.
 *
 * @requirement FR-04 Payment Reconciliation
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
interface PaymentMatchRepositoryInterface
{
    /**
     * Insert a new payment match record.
     *
     * @param StagingPaymentMatch $match The match to insert
     * @return int The auto-generated ID
     */
    public function insert(StagingPaymentMatch $match): int;

    /**
     * Find a payment match by primary key.
     *
     * @param int $id The row ID
     * @return StagingPaymentMatch|null null when not found
     */
    public function findById(int $id): ?StagingPaymentMatch;

    /**
     * Find all match records for a staging payment (newest first).
     *
     * @param int $stagingPaymentId FK to staging_payments
     * @return StagingPaymentMatch[]
     */
    public function findByPaymentId(int $stagingPaymentId): array;

    /**
     * Find match records filtered by match_status.
     *
     * @param string $matchStatus The status to filter on
     * @return StagingPaymentMatch[]
     */
    public function findByStatus(string $matchStatus): array;

    /**
     * Update the match status and optional notes.
     *
     * @param int         $id          The row ID
     * @param string      $matchStatus New match status
     * @param string|null $notes       Optional notes
     */
    public function updateStatus(int $id, string $matchStatus, ?string $notes = null): void;

    /**
     * Get the most recent match record for a staging payment.
     *
     * @param int $stagingPaymentId FK to staging_payments
     * @return StagingPaymentMatch|null null when no matches exist
     */
    public function getLatestByPaymentId(int $stagingPaymentId): ?StagingPaymentMatch;
}
