<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Services;

use Ksfraser\ImportStaging\Contracts\StagingManagerInterface;
use Ksfraser\ImportStaging\Contracts\ValidationResult;
use Ksfraser\ImportStaging\Contracts\ProcessingResult;
use Ksfraser\ImportStaging\Models\StagingCustomer;
use Ksfraser\ImportStaging\Models\StagingTransaction;
use Ksfraser\ImportStaging\Models\StagingPayment;
use Ksfraser\ImportStaging\Models\StagingPaymentMatch;
use Ksfraser\ImportStaging\DAO\StagingCustomerDAO;
use Ksfraser\ImportStaging\DAO\StagingTransactionDAO;
use Ksfraser\ImportStaging\DAO\StagingPaymentDAO;
use Ksfraser\ImportStaging\DAO\StagingPaymentMatchDAO;
use Ksfraser\ImportStaging\DAO\StagingLogDAO;
use Ksfraser\ImportStaging\Exceptions\DuplicateTransactionException;
use Ksfraser\ImportStaging\Exceptions\InvalidSourceException;
use Ksfraser\ImportStaging\Validators\TransactionValidator;
use Ksfraser\ImportStaging\Validators\CustomerValidator;
use Ksfraser\ImportStaging\Validators\PaymentValidator;

class StagingService implements StagingManagerInterface
{
    private StagingCustomerDAO $customerDAO;
    private StagingTransactionDAO $transactionDAO;
    private StagingPaymentDAO $paymentDAO;
    private StagingPaymentMatchDAO $paymentMatchDAO;
    private StagingLogDAO $logDAO;
    private TransactionValidator $transactionValidator;
    private CustomerValidator $customerValidator;
    private PaymentValidator $paymentValidator;
    private MatchingService $matchingService;
    private array $validSources;

    public function __construct(
        StagingCustomerDAO $customerDAO,
        StagingTransactionDAO $transactionDAO,
        StagingPaymentDAO $paymentDAO,
        StagingPaymentMatchDAO $paymentMatchDAO,
        StagingLogDAO $logDAO,
        TransactionValidator $transactionValidator,
        CustomerValidator $customerValidator,
        PaymentValidator $paymentValidator,
        MatchingService $matchingService,
        array $validSources = ['woocommerce', 'square_api', 'square_csv', 'paypal', 'bank']
    ) {
        $this->customerDAO = $customerDAO;
        $this->transactionDAO = $transactionDAO;
        $this->paymentDAO = $paymentDAO;
        $this->paymentMatchDAO = $paymentMatchDAO;
        $this->logDAO = $logDAO;
        $this->transactionValidator = $transactionValidator;
        $this->customerValidator = $customerValidator;
        $this->paymentValidator = $paymentValidator;
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

    public function stageOrUpdateCustomer(array $data, string $source): StagingCustomer
    {
        $this->validateSource($source);
        $customer = StagingCustomer::fromArray(array_merge($data, ['source' => $source]));
        $validation = $this->customerValidator->validate($customer->toArray());
        if (!$validation->isSuccess()) {
            throw \Ksfraser\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        if ($customer->getSourceCustomerId()) {
            $existing = $this->customerDAO->findBySource($source, $customer->getSourceCustomerId());
            if ($existing) {
                $customer->setId($existing->getId());
                $this->customerDAO->updateBySource($customer);
                $this->logDAO->log('customer', $existing->getId(), 'updated', $source);
                return $customer;
            }
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

    public function stageOrUpdateTransaction(array $data, string $source): StagingTransaction
    {
        $this->validateSource($source);
        $transaction = StagingTransaction::fromArray(array_merge($data, ['source' => $source]));
        $validation = $this->transactionValidator->validate($transaction->toArray());
        if (!$validation->isSuccess()) {
            throw \Ksfraser\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        if ($transaction->getSourceTransactionId()) {
            $existing = $this->transactionDAO->findBySource($source, $transaction->getSourceTransactionId());
            if ($existing) {
                $transaction->setId($existing->getId());
                $this->transactionDAO->updateBySource($transaction);
                $this->logDAO->log('transaction', $existing->getId(), 'updated', $source);
                return $transaction;
            }
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
                $existingRecords = $this->findExistingMatchRecords($record);
                $matchResult = $this->matchingService->matchCandidates($record->toArray(), $existingRecords);
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

    public function stagePayment(array $data, string $source, ?int $stagingTransactionId = null): StagingPayment
    {
        $this->validateSource($source);
        if (isset($data['source_payment_id']) && $data['source_payment_id']) {
            $existing = $this->paymentDAO->findBySource($source, $data['source_payment_id']);
            if ($existing) {
                throw \Ksfraser\ImportStaging\Exceptions\DuplicateTransactionException::forSource($source, $data['source_payment_id']);
            }
        }
        $payment = StagingPayment::fromArray(array_merge($data, ['source' => $source]));
        if ($stagingTransactionId !== null) {
            $payment->setStagingTransactionId($stagingTransactionId);
        }
        if ($payment->getNetAmount() === 0.0 && $payment->getAmount() > 0) {
            $payment->setNetAmount($payment->getAmount() - $payment->getFee());
        }
        $validation = $this->paymentValidator->validate($payment->toArray());
        if (!$validation->isSuccess()) {
            throw \Ksfraser\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        $id = $this->paymentDAO->insert($payment);
        $this->logDAO->log('payment', $id, 'staged', $source);
        return $payment;
    }

    public function getStagedPayments(array $filters = []): array
    {
        $status = $filters['status'] ?? 'staged';
        $source = $filters['source'] ?? null;
        return $this->paymentDAO->findByStatus($status, $source);
    }

    public function getPaymentsByTransaction(int $stagingTransactionId): array
    {
        return $this->paymentDAO->findByTransaction($stagingTransactionId);
    }

    public function reconcilePayment(int $paymentId, array $faRecord): ProcessingResult
    {
        $payment = $this->paymentDAO->findById($paymentId);
        if (!$payment) {
            return ProcessingResult::failure(0, 'payment_not_found', ['Payment not found: ' . $paymentId]);
        }

        $confidence = $this->matchingService->calculatePaymentMatchScore(
            $payment->toArray(),
            $faRecord
        );

        $paymentData = $payment->toArray();
        $match = new StagingPaymentMatch($paymentId, $this->matchingService->determinePaymentMatchType($confidence));
        $match->setMatchConfidence($confidence);
        $match->setFaTransType((int)($faRecord['trans_type'] ?? 0));
        $match->setFaTransNo((int)($faRecord['trans_no'] ?? 0));
        $match->setFaBankAccount($faRecord['bank_account'] ?? null);

        if ($this->matchingService->autoApprove($confidence)) {
            $match->setMatchStatus('matched');
            $match->setMatchType('exact');
            $this->paymentDAO->updateStatus($paymentId, 'reconciled', $confidence);
            $this->paymentDAO->updateFaReference(
                $paymentId,
                (int)($faRecord['trans_type'] ?? 0),
                (int)($faRecord['trans_no'] ?? 0),
                $faRecord['bank_account'] ?? null
            );
            $this->paymentMatchDAO->insert($match);
            $this->logDAO->log('payment', $paymentId, 'reconciled', $payment->getSource(), [
                'confidence' => $confidence,
                'match_type' => 'exact',
                'fa_trans_type' => $faRecord['trans_type'] ?? null,
                'fa_trans_no' => $faRecord['trans_no'] ?? null,
            ]);
            return ProcessingResult::success($paymentId, 'payment_reconciled');
        }

        if ($this->matchingService->needsReview($confidence)) {
            $match->setMatchStatus('needs_review');
            $match->setMatchType('fuzzy');
            $this->paymentDAO->updateStatus($paymentId, 'matched', $confidence);
            $this->paymentMatchDAO->insert($match);
            $this->logDAO->log('payment', $paymentId, 'needs_review', $payment->getSource(), [
                'confidence' => $confidence,
            ]);
            return ProcessingResult::success($paymentId, 'payment_needs_review');
        }

        $match->setMatchStatus('rejected');
        $match->setMatchType('none');
        $this->paymentDAO->updateStatus($paymentId, 'unmatched', $confidence);
        $this->paymentMatchDAO->insert($match);
        $this->logDAO->log('payment', $paymentId, 'unmatched', $payment->getSource(), [
            'confidence' => $confidence,
        ]);
        return ProcessingResult::success($paymentId, 'payment_unmatched');
    }

    public function reconcilePaymentQueue(?string $source = null): ProcessingResult
    {
        $payments = $this->paymentDAO->getQueueForReconciliation($source);
        if (empty($payments)) {
            return ProcessingResult::success(0, 'no_payments_to_reconcile');
        }
        $processed = 0;
        $failed = 0;
        $errors = [];
        foreach ($payments as $payment) {
            try {
                $fakeFaRecord = [
                    'amount' => $payment->getAmount(),
                    'payment_date' => $payment->getPaymentDate() ? $payment->getPaymentDate()->format('Y-m-d') : null,
                    'reference' => $payment->getReference(),
                ];
                $result = $this->reconcilePayment($payment->getId(), $fakeFaRecord);
                if ($result->isSuccess()) {
                    $processed++;
                } else {
                    $failed++;
                    $errors[] = sprintf('Payment %d: %s', $payment->getId(), implode(', ', $result->getErrors()));
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = sprintf('Payment %d: %s', $payment->getId(), $e->getMessage());
                $this->paymentDAO->updateStatus($payment->getId(), 'failed', null, $e->getMessage());
                $this->logDAO->log('payment', $payment->getId(), 'failed', $payment->getSource(), [
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if ($failed > 0) {
            return ProcessingResult::failure(0, 'reconciliation_completed', $errors);
        }
        return ProcessingResult::success($processed, 'reconciliation_completed');
    }

    public function getPaymentMatchHistory(int $paymentId): array
    {
        return $this->paymentMatchDAO->findByPaymentId($paymentId);
    }

    public function getPaymentStatusCounts(?string $source = null): array
    {
        return $this->paymentDAO->countByStatus($source);
    }

    /**
     * Search the new FA modules (ksf_FA_Customer, ksf_FA_Payment) for existing
     * records that could match the given staged record. Uses hook_invoke_first
     * to communicate with the sub-modules.
     *
     * Falls back gracefully (empty array) when hooks or modules are unavailable.
     */
    private function findExistingMatchRecords($stagedRecord): array
    {
        $existing = [];

        $customerName = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getCustomerName()
            : ($stagedRecord['customer_name'] ?? null);
        $customerEmail = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getCustomerEmail()
            : ($stagedRecord['customer_email'] ?? null);
        $reference = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getSourceTransactionId()
            : ($stagedRecord['source_transaction_id'] ?? null);

        if (!function_exists('hook_invoke_first')) {
            return $existing;
        }

        if ($customerEmail) {
            $searchData = ['query' => $customerEmail];
            $faCustomers = hook_invoke_first('SEARCH_CUSTOMER', $searchData);
            if (is_array($faCustomers)) {
                foreach ($faCustomers as $faCust) {
                    $existing[] = [
                        'customer_name' => $faCust['name'] ?? '',
                        'customer_email' => $faCust['email'] ?? '',
                        'total_amount' => 0.0,
                        'transaction_date' => null,
                        'source_transaction_id' => $faCust['reference'] ?? '',
                        '_fa_type' => 'customer',
                        '_fa_debtor_no' => $faCust['fa_debtor_no'] ?? $faCust['debtor_no'] ?? 0,
                    ];
                }
            }
        }

        if ($customerName) {
            $searchData = ['query' => $customerName];
            $faCustomers = hook_invoke_first('SEARCH_CUSTOMER', $searchData);
            if (is_array($faCustomers)) {
                foreach ($faCustomers as $faCust) {
                    $alreadyAdded = false;
                    foreach ($existing as $e) {
                        if (($e['_fa_debtor_no'] ?? 0) === ($faCust['fa_debtor_no'] ?? $faCust['debtor_no'] ?? 0)) {
                            $alreadyAdded = true;
                            break;
                        }
                    }
                    if (!$alreadyAdded) {
                        $existing[] = [
                            'customer_name' => $faCust['name'] ?? '',
                            'customer_email' => $faCust['email'] ?? '',
                            'total_amount' => 0.0,
                            'transaction_date' => null,
                            'source_transaction_id' => $faCust['reference'] ?? '',
                            '_fa_type' => 'customer',
                            '_fa_debtor_no' => $faCust['fa_debtor_no'] ?? $faCust['debtor_no'] ?? 0,
                        ];
                    }
                }
            }
        }

        if ($reference) {
            $paymentData = ['reference' => $reference];
            $faPayment = hook_invoke_first('GET_PAYMENT', $paymentData);
            if (is_array($faPayment) && !isset($faPayment['error'])) {
                $existing[] = [
                    'customer_name' => '',
                    'customer_email' => '',
                    'total_amount' => $faPayment['amount'] ?? 0.0,
                    'transaction_date' => $faPayment['payment_date'] ?? null,
                    'source_transaction_id' => $faPayment['reference'] ?? $reference,
                    '_fa_type' => 'payment',
                    '_fa_payment_no' => $faPayment['fa_payment_no'] ?? 0,
                ];
            }
        }

        return $existing;
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
