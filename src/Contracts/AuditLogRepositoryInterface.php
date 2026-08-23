<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use DateTimeInterface;

/**
 * Contract for the staging audit log.
 *
 * Implementations provide write and query operations for the audit trail.
 *
 * @requirement FR-08 Audit Trail
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
interface AuditLogRepositoryInterface
{
    /**
     * Write an audit log entry.
     *
     * @param string      $recordType The entity type
     * @param int         $recordId   The entity ID
     * @param string      $action     The action performed
     * @param string|null $source     Optional source system identifier
     * @param array       $details    Optional key-value details
     * @return int The auto-generated log ID
     */
    public function log(
        string $recordType,
        int $recordId,
        string $action,
        ?string $source = null,
        array $details = []
    ): int;

    /**
     * Find log entries for a specific entity.
     *
     * @param string $recordType The entity type
     * @param int    $recordId   The entity ID
     * @return array<int,array<string,mixed>> Raw row arrays
     */
    public function findByRecord(string $recordType, int $recordId): array;

    /**
     * Find log entries by action, optionally filtered by source.
     *
     * @param string      $action The action to filter on
     * @param string|null $source Optional source filter
     * @param int         $limit  Maximum records to return
     * @return array<int,array<string,mixed>> Raw row arrays
     */
    public function findByAction(string $action, ?string $source = null, int $limit = 100): array;

    /**
     * Find log entries within a date range.
     *
     * @param DateTimeInterface $from   Start datetime (inclusive)
     * @param DateTimeInterface $to     End datetime (inclusive)
     * @param string|null       $action Optional action filter
     * @return array<int,array<string,mixed>> Raw row arrays
     */
    public function findByDateRange(DateTimeInterface $from, DateTimeInterface $to, ?string $action = null): array;

    /**
     * Get the most recent log entries.
     *
     * @param int $limit Maximum records to return
     * @return array<int,array<string,mixed>> Raw row arrays
     */
    public function getRecent(int $limit = 50): array;

    /**
     * Count log entries grouped by action.
     *
     * @param string|null $source Optional source filter
     * @return array<string,int> Map of action => count
     */
    public function countByAction(?string $source = null): array;
}
