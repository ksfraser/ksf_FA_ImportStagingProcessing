<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;

/**
 * Contract for customer staging data access.
 *
 * Implementations provide CRUD operations for StagingCustomer records.
 * Allows different data sources to supply their own persistence layer
 * behind a unified interface.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-001-001 customer staging
 * @BABOK Related: FR-ISU-005-001 duplicate detection (findBySource)
 * @BABOK Related: NFR-ISU-009 Repository pattern
 */
interface CustomerRepositoryInterface
{
    /**
     * Insert a new staging customer.
     *
     * @param StagingCustomer $customer The customer to insert
     * @return int The auto-generated ID
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 insert
     */
    public function insert(StagingCustomer $customer): int;

    /**
     * Find a staging customer by primary key.
     *
     * @param int $id The row ID
     * @return StagingCustomer|null null when not found
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 findById
     */
    public function findById(int $id): ?StagingCustomer;

    /**
     * Find a staging customer by source + source-side customer ID.
     *
     * @param string $source             The source system (e.g. 'square_api')
     * @param string $sourceCustomerId   The source-side identifier
     * @return StagingCustomer|null null when not found
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 findBySource
     */
    public function findBySource(string $source, string $sourceCustomerId): ?StagingCustomer;

    /**
     * Find staging customers filtered by status and optionally source.
     *
     * @param string      $status The status to filter on
     * @param string|null $source Optional source filter
     * @return StagingCustomer[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 findByStatus
     */
    public function findByStatus(string $status, ?string $source = null): array;

    /**
     * Find staging customers by email address.
     *
     * @param string $email The email to search for
     * @return StagingCustomer[]
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 findByEmail
     */
    public function findByEmail(string $email): array;

    /**
     * Update the status (and optional error log) of a staging customer.
     *
     * @param int         $id     The row ID
     * @param string      $status New status value
     * @param string|null $error  Optional error message
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updateStatus
     */
    public function updateStatus(int $id, string $status, ?string $error = null): void;

    /**
     * Update a staging customer by its source + source_customer_id key.
     *
     * @param StagingCustomer $customer The customer with updated fields
     * @return bool True if a row was affected
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 updateBySource
     */
    public function updateBySource(StagingCustomer $customer): bool;

    /**
     * Count staging customers grouped by status.
     *
     * @param string|null $source Optional source filter
     * @return array<string,int> Map of status => count
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 countByStatus
     */
    public function countByStatus(?string $source = null): array;
}
