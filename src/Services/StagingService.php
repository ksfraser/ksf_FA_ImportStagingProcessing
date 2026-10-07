<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services;

use ksfraser\FrontAccounting\ImportStaging\Contracts\StagingManagerInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ValidationResult;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ProcessingResult;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingCustomer;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingTransaction;
use ksfraser\FrontAccounting\ImportStaging\Services\Dispatch\CreationInvoker;
use ksfraser\FrontAccounting\ImportStaging\Services\Dispatch\SearchInvoker;
use ksfraser\FrontAccounting\ImportStaging\Services\Matching\MatchConfig;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingPayment;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingPaymentMatch;
use ksfraser\FrontAccounting\ImportStaging\Models\StagingLineItem;
use ksfraser\FrontAccounting\ImportStaging\Contracts\CustomerRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\TransactionRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentMatchRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\LineItemRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\AuditLogRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\DuplicateTransactionException;
use ksfraser\FrontAccounting\ImportStaging\Exceptions\InvalidSourceException;
use ksfraser\FrontAccounting\ImportStaging\Validators\TransactionValidator;
use ksfraser\FrontAccounting\ImportStaging\Validators\CustomerValidator;
use ksfraser\FrontAccounting\ImportStaging\Validators\PaymentValidator;

class StagingService implements StagingManagerInterface
{
    private CustomerRepositoryInterface $customerDAO;
    private TransactionRepositoryInterface $transactionDAO;
    private PaymentRepositoryInterface $paymentDAO;
    private PaymentMatchRepositoryInterface $paymentMatchDAO;
    private LineItemRepositoryInterface $lineItemDAO;
    private AuditLogRepositoryInterface $logDAO;
    private TransactionValidator $transactionValidator;
    private CustomerValidator $customerValidator;
    private PaymentValidator $paymentValidator;
    private MatchingService $matchingService;
    private array $validSources;

    public function __construct(
        CustomerRepositoryInterface $customerDAO,
        TransactionRepositoryInterface $transactionDAO,
        PaymentRepositoryInterface $paymentDAO,
        PaymentMatchRepositoryInterface $paymentMatchDAO,
        LineItemRepositoryInterface $lineItemDAO,
        AuditLogRepositoryInterface $logDAO,
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
        $this->lineItemDAO = $lineItemDAO;
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
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
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
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
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
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
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
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
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
                throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\DuplicateTransactionException::forSource($source, $data['source_payment_id']);
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
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        $id = $this->paymentDAO->insert($payment);
        $this->logDAO->log('payment', $id, 'staged', $source);
        return $payment;
    }

    public function stageOrUpdatePayment(array $data, string $source, ?int $stagingTransactionId = null): StagingPayment
    {
        $this->validateSource($source);
        $payment = StagingPayment::fromArray(array_merge($data, ['source' => $source]));
        if ($stagingTransactionId !== null) {
            $payment->setStagingTransactionId($stagingTransactionId);
        }
        if ($payment->getNetAmount() === 0.0 && $payment->getAmount() > 0) {
            $payment->setNetAmount($payment->getAmount() - $payment->getFee());
        }
        $validation = $this->paymentValidator->validate($payment->toArray());
        if (!$validation->isSuccess()) {
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed($validation->getErrors());
        }
        if ($payment->getSourcePaymentId()) {
            $existing = $this->paymentDAO->findBySource($source, $payment->getSourcePaymentId());
            if ($existing) {
                $payment->setId($existing->getId());
                $this->paymentDAO->updateBySource($payment);
                $this->logDAO->log('payment', $existing->getId(), 'updated', $source);
                return $payment;
            }
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

    // ========================================================================
    // Line item staging
    // ========================================================================

    public function stageLineItem(array $data, string $source): StagingLineItem
    {
        $this->validateSource($source);
        $item = StagingLineItem::fromArray(array_merge($data, ['source' => $source]));
        if ($item->getStagingTransactionId() <= 0) {
            throw \ksfraser\FrontAccounting\ImportStaging\Exceptions\StagingException::validationFailed(
                ['staging_transaction_id is required']
            );
        }
        $id = $this->lineItemDAO->insert($item);
        $this->logDAO->log('line_item', $id, 'staged', $source);
        return $item;
    }

    public function stageOrUpdateLineItem(array $data, string $source): StagingLineItem
    {
        $this->validateSource($source);
        $item = StagingLineItem::fromArray(array_merge($data, ['source' => $source]));
        if ($item->getSourceId()) {
            $existing = $this->lineItemDAO->findBySource($source, $item->getSourceId());
            if (!empty($existing)) {
                $this->lineItemDAO->updateBySource($item);
                $this->logDAO->log('line_item', $existing[0]->getId(), 'updated', $source);
                return $item;
            }
        }
        $id = $this->lineItemDAO->insert($item);
        $this->logDAO->log('line_item', $id, 'staged', $source);
        return $item;
    }

    public function getLineItemsByTransaction(int $stagingTransactionId): array
    {
        return $this->lineItemDAO->findByTransactionId($stagingTransactionId);
    }

    public function getLineItemsBySource(string $source, ?string $sourceId = null): array
    {
        return $this->lineItemDAO->findBySource($source, $sourceId);
    }

    public function deleteLineItemsByTransaction(int $stagingTransactionId): void
    {
        $this->lineItemDAO->deleteByTransactionId($stagingTransactionId);
        $this->logDAO->log('line_item', $stagingTransactionId, 'deleted', null);
    }

/**
     * Search every active module for records that could match this staged one.
     *
     * A lookup uses hook_invoke_all, NOT first-answer-wins: "have we already
     * got this person?" is legitimately answered by several modules at once -- an
     * FA debtor (ksf_FA_Customer), an employee (ksf_FA_HRM), a CRM contact
     * (ksf_FA_CRM), a login (ksf_FA_RBAC). Taking only the first answer would
     * hide exactly the collision dedupe exists to catch. Creation is the mirror
     * image -- one provider, so that is dispatched by capability through
     * CreationInvoker.
     *
     * The +/-N day window and the amount tolerance come from MatchConfig and are
     * handed to the providers rather than applied here, so each can filter in SQL
     * instead of fetching everything and discarding it locally.
     *
     * @param StagingTransaction|array $stagedRecord
     * @return array
     */
    private function findExistingMatchRecords($stagedRecord): array
    {
        $existing = [];

        if (!function_exists('hook_invoke_all')) {
            return $existing;
        }

        $search = new SearchInvoker($this->matchConfig());

        $customerName = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getCustomerName()
            : ($stagedRecord['customer_name'] ?? null);
        $customerEmail = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getCustomerEmail()
            : ($stagedRecord['customer_email'] ?? null);
        $reference = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getSourceTransactionId()
            : ($stagedRecord['source_transaction_id'] ?? null);
        $date = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getTransactionDate()
            : ($stagedRecord['transaction_date'] ?? null);
        $amount = $stagedRecord instanceof StagingTransaction
            ? $stagedRecord->getTotalAmount()
            : ($stagedRecord['total_amount'] ?? null);

        // Email is the strongest signal, so it gets its own pass; the name pass
        // then adds anything the email pass missed.
        if ($customerEmail) {
            $existing = array_merge($existing, $this->searchCustomers(
                $search,
                array('query' => $customerEmail, 'email' => $customerEmail),
                $date,
                $amount
            ));
        }

        if ($customerName) {
            $existing = array_merge($existing, $this->searchCustomers(
                $search,
                array('query' => $customerName, 'name' => $customerName),
                $date,
                $amount
            ));
        }

        if ($reference) {
            $existing = array_merge($existing, $this->searchPayments(
                $search,
                array('query' => $reference, 'reference' => $reference),
                $date,
                $amount
            ));
        }

        return $this->dedupeExisting($existing);
    }

    /**
     * Run a customer search across all providers and normalise the rows.
     *
     * @param SearchInvoker $search
     * @param array         $criteria
     * @param string|null   $date
     * @param float|null    $amount
     * @return array
     */
    private function searchCustomers(SearchInvoker $search, array $criteria, $date = null, $amount = null): array
    {
        if ($amount !== null) {
            $criteria['amount'] = $amount;
        }

        $result = $search->search('SEARCH_CUSTOMER', $criteria, $date);
        $rows = array();

        foreach ($result['candidates'] as $candidate) {
            $rows[] = array(
                'customer_name' => $candidate['name'] ?? ($candidate['company'] ?? ''),
                'customer_email' => $candidate['email'] ?? '',
                'customer_phone' => $candidate['phone'] ?? '',
                'total_amount' => $amount === null ? 0.0 : (float)$amount,
                'transaction_date' => $candidate['date'] ?? $date,
                'source_transaction_id' => $candidate['reference'] ?? '',
                // Provenance matters: the reviewer must be able to tell a match
                // against an HRM employee from a match against an FA debtor,
                // because that changes whether a new debtor should be created.
                '_found_by' => $candidate['_module'] ?? 'unknown',
                '_entity' => $candidate['_entity'] ?? '',
                '_fa_type' => 'customer',
                '_fa_debtor_no' => $candidate['fa_debtor_no'] ?? ($candidate['debtor_no'] ?? 0),
            );
        }

        return $rows;
    }

    /**
     * Run a payment search across all providers and normalise the rows.
     *
     * @param SearchInvoker $search
     * @param array         $criteria
     * @param string|null   $date
     * @param float|null    $amount
     * @return array
     */
    private function searchPayments(SearchInvoker $search, array $criteria, $date = null, $amount = null): array
    {
        if ($amount !== null) {
            $criteria['amount'] = $amount;
        }

        $result = $search->search('SEARCH_PAYMENT', $criteria, $date);
        $rows = array();

        foreach ($result['candidates'] as $candidate) {
            $rows[] = array(
                'customer_name' => '',
                'customer_email' => '',
                'total_amount' => (float)($candidate['amount'] ?? ($amount ?? 0.0)),
                'transaction_date' => $candidate['payment_date'] ?? ($candidate['date'] ?? $date),
                'source_transaction_id' => $candidate['reference'] ?? '',
                '_found_by' => $candidate['_module'] ?? 'unknown',
                '_entity' => $candidate['_entity'] ?? '',
                '_fa_type' => 'payment',
                '_fa_payment_no' => $candidate['fa_payment_no'] ?? ($candidate['trans_no'] ?? 0),
                // Carried through so a charge already recorded by the other
                // source system (a Woo order also seen in Square) can be
                // recognised instead of posted twice.
                '_source' => $candidate['source'] ?? null,
                '_source_payment_id' => $candidate['source_payment_id'] ?? null,
            );
        }

        return $rows;
    }

    /**
     * Collapse rows that describe the same existing record.
     *
     * Keyed on the record, not the name: the same person surfacing as both an
     * HRM employee and an FA debtor is one person with two sources, not two
     * candidates for a reviewer to choose between.
     *
     * @param array $rows
     * @return array
     */
    private function dedupeExisting(array $rows): array
    {
        $seen = array();
        $out = array();

        foreach ($rows as $row) {
            $type = $row['_fa_type'] ?? '';
            $id = $type === 'payment'
                ? ($row['_fa_payment_no'] ?? 0)
                : ($row['_fa_debtor_no'] ?? 0);

            $key = $type . '|' . $row['_found_by'] . '|' . $id;
            if ($id && isset($seen[$key])) {
                continue;
            }
            if ($id) {
                $seen[$key] = true;
            }

            $out[] = $row;
        }

        return $out;
    }

    /**
     * Match tunables for this service.
     *
     * @return MatchConfig
     */
    private function matchConfig(): MatchConfig
    {
        return new MatchConfig();
    }

    /**
     * Create a new FA debtor/branch/contact from a staged customer.
     *
     * Dispatched by CAPABILITY, not by module name: CreationInvoker asks
     * whichever active module provides CREATE_CUSTOMER. Today that is
     * ksf_FA_Customer; a future SuiteCRM migration module should be able to
     * answer instead without this method being edited.
     */
    public function createDebtorFromStaged(StagingCustomer $stagedCustomer): array
    {
        $fullName = $stagedCustomer->getName() ?? '';
        $parts = explode(' ', $fullName, 2);
        
        $requestData = [
            'action' => 'create_customer',
            'name' => $fullName,
            'first_name' => $parts[0] ?? '',
            'last_name' => $parts[1] ?? '',
            'email' => $stagedCustomer->getEmail() ?? '',
            'phone' => $stagedCustomer->getPhone() ?? '',
            'address' => trim(($stagedCustomer->getAddressLine1() ?? '') . ' ' . ($stagedCustomer->getAddressLine2() ?? '')),
            'source_customer_id' => $stagedCustomer->getSourceCustomerId()
        ];

        $invoker = new CreationInvoker();
        $result = $invoker->create('CREATE_CUSTOMER', $requestData);

        // Distinguish "nobody provides CREATE_CUSTOMER" from "the provider
        // refused". Both must fail loudly: reporting a debtor number that was
        // never created is the exact defect this pipeline exists to prevent.
        if (!$result['handled']) {
            throw new \RuntimeException('Failed to create FA debtor: ' . $result['error']);
        }

        $response = $result['response'];
        if (empty($response['success'])) {
            throw new \RuntimeException(
                'Failed to create FA debtor: ' . ($response['error'] ?? 'Unknown error')
            );
        }

        $debtorNo = $response['fa_debtor_no'] ?? null;
        if ($debtorNo === null) {
            throw new \RuntimeException(
                'CREATE_CUSTOMER reported success without a fa_debtor_no'
            );
        }

        $stagedCustomer->setFaDebtorNo((int)$debtorNo);
        $this->customerDAO->updateBySource($stagedCustomer);

        return array_merge($requestData, [
            'staged_customer_id' => $stagedCustomer->getId(),
            'created_by' => $result['module'],
            'fa_debtor_no' => (int)$debtorNo,
            'branch_code' => $response['branch_code'] ?? null,
            'contact_id' => $response['person_id'] ?? ($response['contact_id'] ?? null),
        ]);
    }

    /**
     * Map staged customer to existing FA debtor/branch/contact.
     */
    public function mapStagedCustomerToExisting(StagingCustomer $stagedCustomer, array $existingDebtor, ?int $branchCode = null, ?string $contactRef = null): array
    {
        $stagedCustomer->setFaDebtorNo($existingDebtor['debtor_no']);
        $this->customerDAO->updateBySource($stagedCustomer);
        return [
            'staged_customer_id' => $stagedCustomer->getId(),
            'fa_debtor_no' => $existingDebtor['debtor_no'],
            'branch_code' => $branchCode,
            'contact_ref' => $contactRef,
            'mapped_at' => date('Y-m-d H:i:s'),
        ];
    }
}
