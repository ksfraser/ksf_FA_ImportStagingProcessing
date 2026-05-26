<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Services;

use Ksfraser\ImportStaging\Contracts\StagingManagerInterface;
use Ksfraser\ImportStaging\Contracts\ValidationResult;
use Ksfraser\ImportStaging\Contracts\ProcessingResult;
use Ksfraser\ImportStaging\Models\StagingCustomer;
use Ksfraser\ImportStaging\Models\StagingTransaction;
use Ksfraser\ImportStaging\DAO\StagingCustomerDAO;
use Ksfraser\ImportStaging\DAO\StagingTransactionDAO;
use Ksfraser\ImportStaging\DAO\StagingLogDAO;
use Ksfraser\ImportStaging\Exceptions\DuplicateTransactionException;
use Ksfraser\ImportStaging\Exceptions\InvalidSourceException;
use Ksfraser\ImportStaging\Validators\TransactionValidator;
use Ksfraser\ImportStaging\Validators\CustomerValidator;

class StagingService implements StagingManagerInterface
{
    private StagingCustomerDAO $customerDAO;
    private StagingTransactionDAO $transactionDAO;
    private StagingLogDAO $logDAO;
    private TransactionValidator $transactionValidator;
    private CustomerValidator $customerValidator;
    private MatchingService $matchingService;
    private array $validSources;

    public function __construct(
        StagingCustomerDAO $customerDAO,
        StagingTransactionDAO $transactionDAO,
        StagingLogDAO $logDAO,
        TransactionValidator $transactionValidator,
        CustomerValidator $customerValidator,
        MatchingService $matchingService,
        array $validSources = ['woocommerce', 'square_api', 'square_csv', 'paypal', 'bank']
    ) {
        $this->customerDAO = $customerDAO;
        $this->transactionDAO = $transactionDAO;
        $this->logDAO = $logDAO;
        $this->transactionValidator = $transactionValidator;
        $this->customerValidator = $customerValidator;
        $this->matchingService = $matchingService;
        $this->validSources = $validSources;
    }

    public function stageCustomer(array $data, string $source): StagingCustomer
    {
        $this->validateSource($source);
        $customer = StagingCustomer::fromArray(array_merge($data, ['source' => $source]));
        $validation = $this->customerValidator->validate($customer->toArray());
        if (!$validation->isSuccess()) {
            throw \Ksfraser\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        $id = $this->customerDAO->insert($customer);
        $this->logDAO->log('customer', $id, 'staged', $source);
        return $customer;
    }

    public function stageTransaction(array $data, string $source): StagingTransaction
    {
        $this->validateSource($source);
        if (isset($data['source_transaction_id']) && $data['source_transaction_id']) {
            $existing = $this->transactionDAO->findBySource($source, $data['source_transaction_id']);
            if ($existing) {
                throw DuplicateTransactionException::forSource($source, $data['source_transaction_id']);
            }
        }
        $transaction = StagingTransaction::fromArray(array_merge($data, ['source' => $source]));
        $validation = $this->transactionValidator->validate($transaction->toArray());
        if (!$validation->isSuccess()) {
            throw \Ksfraser\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        $id = $this->transactionDAO->insert($transaction);
        $this->logDAO->log('transaction', $id, 'staged', $source);
        return $transaction;
    }

    public function getStagedCustomers(array $filters = []): array
    {
        $status = $filters['status'] ?? 'staged';
        $source = $filters['source'] ?? null;
        return $this->customerDAO->findByStatus($status, $source);
    }

    public function getStagedTransactions(array $filters = []): array
    {
        $status = $filters['status'] ?? 'staged';
        $source = $filters['source'] ?? null;
        return $this->transactionDAO->findByStatus($status, $source);
    }

    public function updateStatus(int $id, string $status, ?string $error = null): void
    {
        $this->transactionDAO->updateStatus($id, $status, null, $error);
        $this->logDAO->log('transaction', $id, $status, null, $error ? ['error' => $error] : []);
    }

    public function processQueue(?string $source = null): ProcessingResult
    {
        $records = $this->transactionDAO->getQueueForProcessing($source);
        if (empty($records)) {
            return ProcessingResult::success(0, 'no_records');
        }
        $processed = 0;
        $failed = 0;
        $errors = [];
        foreach ($records as $record) {
            try {
                $matchResult = $this->matchingService->matchCandidates($record->toArray(), []);
                $confidence = $matchResult['confidence'] ?? 0.0;
                if ($this->matchingService->autoApprove($confidence)) {
                    $this->transactionDAO->updateStatus($record->getId(), 'matched', $confidence);
                    $this->logDAO->log('transaction', $record->getId(), 'matched', $record->getSource(), [
                        'confidence' => $confidence,
                        'match_type' => 'auto_approve',
                    ]);
                    $processed++;
                } elseif ($this->matchingService->needsReview($confidence)) {
                    $this->transactionDAO->updateStatus($record->getId(), 'needs_review', $confidence);
                    $this->logDAO->log('transaction', $record->getId(), 'needs_review', $record->getSource(), [
                        'confidence' => $confidence,
                    ]);
                    $processed++;
                } else {
                    $this->transactionDAO->updateStatus($record->getId(), 'unmatched', $confidence);
                    $this->logDAO->log('transaction', $record->getId(), 'unmatched', $record->getSource(), [
                        'confidence' => $confidence,
                    ]);
                    $processed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = sprintf('Record %d: %s', $record->getId(), $e->getMessage());
                $this->transactionDAO->updateStatus($record->getId(), 'failed', null, $e->getMessage());
                $this->logDAO->log('transaction', $record->getId(), 'failed', $record->getSource(), [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if ($failed > 0) {
            return ProcessingResult::failure(0, 'queue_processed', $errors);
        }
        return ProcessingResult::success($processed, 'queue_processed');
    }

    private function validateSource(string $source): void
    {
        if (empty($source)) {
            throw InvalidSourceException::emptySource();
        }
        if (!in_array($source, $this->validSources, true)) {
            throw InvalidSourceException::unknownSource($source);
        }
    }
}
