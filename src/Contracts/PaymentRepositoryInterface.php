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
 * @requirement FR-03 Payment Staging
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
interface PaymentRepositoryInterface
{
    /**
     * Insert a new staging payment.
     *
     * @param StagingPayment $payment The payment to insert
     * @return int The auto-generated ID
     */
    public function insert(StagingPayment $payment): int;

    /**
     * Find a staging payment by primary key.
     *
     * @param int $id The row ID
     * @return StagingPayment|null null when not found
     */
    public function findById(int $id): ?StagingPayment;

    /**
     * Find a staging payment by source + source_payment_id.
     *
     * @param string $source           The source system
     * @param string $sourcePaymentId  The source-side payment identifier
     * @return StagingPayment|null null when not found
     */
    public function findBySource(string $source, string $sourcePaymentId): ?StagingPayment;

    /**
     * Find staging payments filtered by status and optionally source.
     *
     * @param string      $status The status to filter on
     * @param string|null $source Optional source filter
     * @return StagingPayment[]
     */
    public function findByStatus(string $status, ?string $source = null): array;

    /**
     * Find staging payments within a date range.
     *
     * @param DateTimeInterface $from   Start date (inclusive)
     * @param DateTimeInterface $to     End date (inclusive)
     * @param string|null       $source Optional source filter
     * @return StagingPayment[]
     */
    public function findByDateRange(DateTimeInterface $from, DateTimeInterface $to, ?string $source = null): array;

    /**
     * Find all staging payments linked to a staging transaction.
     *
     * @param int $stagingTransactionId FK to staging_transactions
     * @return StagingPayment[]
     */
    public function findByTransaction(int $stagingTransactionId): array;

    /**
     * Update the status and optional confidence/error of a staging payment.
     *
     * @param int         $id         The row ID
     * @param string      $status     New status value
     * @param float|null  $confidence Optional match confidence
     * @param string|null $error      Optional error message
     */
    public function updateStatus(int $id, string $status, ?float $confidence = null, ?string $error = null): void;

    /**
     * Write the FA payment reference onto a staging payment.
     *
     * @param int         $id          The row ID
     * @param int         $faTransType FA transaction type
     * @param int         $faTransNo   FA transaction number
     * @param string|null $faBankAccount Optional bank account
     */
    public function updateFaReference(int $id, int $faTransType, int $faTransNo, ?string $faBankAccount = null): void;

    /**
     * Update a staging payment by its source + source_payment_id key.
     *
     * @param StagingPayment $payment The payment with updated fields
     * @return bool True if a row was affected
     */
    public function updateBySource(StagingPayment $payment): bool;

    /**
     * Get payments ready for reconciliation (staged, validated, or matched).
     *
     * @param string|null $source Optional source filter
     * @param int         $limit  Maximum records to return
     * @return StagingPayment[]
     */
    public function getQueueForReconciliation(?string $source = null, int $limit = 100): array;

    /**
     * Count staging payments grouped by status.
     *
     * @param string|null $source Optional source filter
     * @return array<string,int> Map of status => count
     */
    public function countByStatus(?string $source = null): array;
}
