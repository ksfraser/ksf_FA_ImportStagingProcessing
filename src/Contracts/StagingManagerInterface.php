<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Contracts;

use Ksfraser\ImportStaging\Models\StagingCustomer;
use Ksfraser\ImportStaging\Models\StagingTransaction;
use Ksfraser\ImportStaging\Models\StagingLineItem;

interface StagingManagerInterface
{
    public function stageCustomer(array $data, string $source): StagingCustomer;

    public function stageOrUpdateCustomer(array $data, string $source): StagingCustomer;

    public function stageTransaction(array $data, string $source): StagingTransaction;

    public function stageOrUpdateTransaction(array $data, string $source): StagingTransaction;

    public function getStagedCustomers(array $filters = []): array;

    public function getStagedTransactions(array $filters = []): array;

    public function updateStatus(int $id, string $status, ?string $error = null): void;

    public function processQueue(?string $source = null): ProcessingResult;

    /**
     * Stage a line item belonging to a staging transaction.
     *
     * Core columns are persisted to staging_line_items; extra source-specific
     * fields in the 'attributes' key go to staging_line_item_attributes.
     *
     * @param array $data {
     *     @var int    $staging_transaction_id FK to staging_transactions
     *     @var string $source
     *     @var string $source_id             Optional source line item ID
     *     @var string $source_updated_at     Optional source version timestamp
     *     @var int    $line_number
     *     @var string $sku
     *     @var string $name
     *     @var string $description
     *     @var string $item_type             product|shipping|discount|fee|tax
     *     @var float  $quantity
     *     @var float  $unit_price
     *     @var float  $tax_amount
     *     @var float  $tax_percent
     *     @var float  $discount_amount
     *     @var float  $discount_percent
     *     @var float  $total_amount
     *     @var string $currency
     *     @var array  $attributes            Name-value pairs for source-specific fields
     * }
     * @param string $source
     * @return StagingLineItem
     */
    public function stageLineItem(array $data, string $source): StagingLineItem;

    /**
     * Upsert a line item by source + source_id.
     */
    public function stageOrUpdateLineItem(array $data, string $source): StagingLineItem;

    /**
     * Get all line items for a staging transaction.
     *
     * @param int $stagingTransactionId
     * @return StagingLineItem[]
     */
    public function getLineItemsByTransaction(int $stagingTransactionId): array;

    /**
     * Get line items by source (and optional source_id).
     *
     * @param string      $source
     * @param string|null $sourceId
     * @return StagingLineItem[]
     */
    public function getLineItemsBySource(string $source, ?string $sourceId = null): array;

    /**
     * Delete all line items for a staging transaction.
     *
     * @param int $stagingTransactionId
     */
    public function deleteLineItemsByTransaction(int $stagingTransactionId): void;
}
