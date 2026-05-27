<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Contracts;

use Ksfraser\ImportStaging\Models\StagingCustomer;
use Ksfraser\ImportStaging\Models\StagingTransaction;

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
}
