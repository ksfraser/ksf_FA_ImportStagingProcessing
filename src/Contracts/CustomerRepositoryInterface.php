<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;

/**
 * Contract for customer staging data access.
 *
 * Implementations provide CRUD operations for StagingCustomer records.
 * Allows different data sources (ISU tables, Square, WooCommerce) to
 * supply their own persistence layer behind a unified interface.
 *
 * @requirement FR-01 Customer Staging
 * @UML Note: Class diagram in ProjectDocs/UML.md
 */
interface CustomerRepositoryInterface
{
    /**
     * Insert a new staging customer.
     *
     * @param StagingCustomer $customer The customer to insert
     * @return int The auto-generated ID
     */
    public function insert(StagingCustomer $customer): int;

    /**
     * Find a staging customer by primary key.
     *
     * @param int $id The row ID
     * @return StagingCustomer|null null when not found
     */
    public function findById(int $id): ?StagingCustomer;

    /**
     * Find a staging customer by source + source-side customer ID.
     *
     * @param string $source             The source system (e.g. 'square_api')
     * @param string $sourceCustomerId   The source-side identifier
     * @return StagingCustomer|null null when not found
     */
    public function findBySource(string $source, string $sourceCustomerId): ?StagingCustomer;

    /**
     * Find staging customers filtered by status and optionally source.
     *
     * @param string      $status The status to filter on
     * @param string|null $source Optional source filter
     * @return StagingCustomer[]
     */
    public function findByStatus(string $status, ?string $source = null): array;

    /**
     * Find staging customers by email address.
     *
     * @param string $email The email to search for
     * @return StagingCustomer[]
     */
    public function findByEmail(string $email): array;

    /**
     * Update the status (and optional error log) of a staging customer.
     *
     * @param int         $id     The row ID
     * @param string      $status New status value
     * @param string|null $error  Optional error message
     */
    public function updateStatus(int $id, string $status, ?string $error = null): void;

    /**
     * Update a staging customer by its source + source_customer_id key.
     *
     * @param StagingCustomer $customer The customer with updated fields
     * @return bool True if a row was affected
     */
    public function updateBySource(StagingCustomer $customer): bool;

    /**
     * Count staging customers grouped by status.
     *
     * @param string|null $source Optional source filter
     * @return array<string,int> Map of status => count
     */
    public function countByStatus(?string $source = null): array;
}
