<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services;

use ksfraser\FrontAccounting\ImportStaging\Contracts\ProcessingResult;
use ksfraser\FrontAccounting\ImportStaging\Contracts\ProcessorInterface;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingCustomerDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingTransactionDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingPaymentDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingLineItemDAO;
use ksfraser\FrontAccounting\ImportStaging\DAO\StagingLogDAO;

/**
 * Orchestrates the processing pipeline: takes matched/approved staging records
 * and creates corresponding FA entities via the Customer and Payment modules.
 *
 * @requirement FR-02.05 Process approved matches
 * @UML Note: Part of ProcessingPipeline in ProjectDocs/UML.md
 */
class ProcessingPipeline implements ProcessorInterface
{
    private StagingCustomerDAO $customerDAO;
    private StagingTransactionDAO $transactionDAO;
    private StagingPaymentDAO $paymentDAO;
    private StagingLineItemDAO $lineItemDAO;
    private StagingLogDAO $logDAO;
    private array $processedIds;

    public function __construct(
        StagingCustomerDAO $customerDAO,
        StagingTransactionDAO $transactionDAO,
        StagingPaymentDAO $paymentDAO,
        StagingLineItemDAO $lineItemDAO,
        StagingLogDAO $logDAO
    ) {
        $this->customerDAO = $customerDAO;
        $this->transactionDAO = $transactionDAO;
        $this->paymentDAO = $paymentDAO;
        $this->lineItemDAO = $lineItemDAO;
        $this->logDAO = $logDAO;
        $this->processedIds = [];
    }

    public function process(array $record, array $context = []): ProcessingResult
    {
        $type = $context['type'] ?? $record['_type'] ?? 'transaction';

        return match ($type) {
            'customer' => $this->processSingleCustomer($record),
            'payment' => $this->processSinglePayment($record),
            default => ProcessingResult::failure(0, 'unsupported_type', ["Unsupported type: $type"]),
        };
    }

    public function canProcess(array $record): bool
    {
        $status = $record['status'] ?? '';
        return in_array($status, ['matched', 'approved', 'reconciled'], true);
    }

    /**
     * Process a single customer record directly (bypasses queue).
     */
    private function processSingleCustomer(array $data): ProcessingResult
    {
        try {
            $faData = [
                'name' => $data['name'] ?? 'Unknown',
                'email' => $data['email'] ?? null,
                'reference' => $data['source_customer_id'] ?? $data['reference'] ?? null,
                'address' => $data['address'] ?? null,
            ];
            $result = $this->invokeCustomerCreation($faData);

            if ($result !== null && !isset($result['error'])) {
                return ProcessingResult::success(
                    0,
                    'customer_processed',
                    (int)($result['fa_debtor_no'] ?? $result['debtor_no'] ?? 0)
                );
            }
            return ProcessingResult::failure(0, 'customer_failed', [$result['error'] ?? 'Customer creation returned null']);
        } catch (\Exception $e) {
            return ProcessingResult::failure(0, 'customer_failed', [$e->getMessage()]);
        }
    }

    /**
     * Process a single payment record directly (bypasses queue).
     */
    private function processSinglePayment(array $data): ProcessingResult
    {
        try {
            $faData = [
                'customer_id' => $data['customer_id'] ?? $data['fa_trans_no'] ?? 0,
                'amount' => $data['net_amount'] ?? $data['amount'] ?? 0,
                'reference' => $data['reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                'memo' => $data['memo'] ?? 'Payment import',
            ];
            $result = $this->invokePaymentCreation($faData);

            if ($result !== null && !isset($result['error'])) {
                return ProcessingResult::success(
                    0,
                    'payment_processed',
                    (int)($result['fa_payment_no'] ?? $result['trans_no'] ?? 0)
                );
            }
            return ProcessingResult::failure(0, 'payment_failed', [$result['error'] ?? 'Payment creation returned null']);
        } catch (\Exception $e) {
            return ProcessingResult::failure(0, 'payment_failed', [$e->getMessage()]);
        }
    }

    /**
     * Process the full queue: customers first, then transactions, then payments.
     * Only processes records in "approved" or "matched" status.
     */
    public function processAll(?string $source = null): ProcessingResult
    {
        $processed = 0;
        $failed = 0;
        $errors = [];

        $customerResult = $this->processCustomers($source);
        $processed += $customerResult->getRecordId() ?? 0;
        if (!$customerResult->isSuccess()) {
            $failed += count($customerResult->getErrors());
            $errors = array_merge($errors, $customerResult->getErrors());
        }

        $transactionResult = $this->processTransactions($source);
        $processed += $transactionResult->getRecordId() ?? 0;
        if (!$transactionResult->isSuccess()) {
            $failed += count($transactionResult->getErrors());
            $errors = array_merge($errors, $transactionResult->getErrors());
        }

        $paymentResult = $this->processPayments($source);
        $processed += $paymentResult->getRecordId() ?? 0;
        if (!$paymentResult->isSuccess()) {
            $failed += count($paymentResult->getErrors());
            $errors = array_merge($errors, $paymentResult->getErrors());
        }

        if ($failed > 0) {
            return ProcessingResult::failure($processed, 'pipeline_completed', $errors);
        }
        return ProcessingResult::success($processed, 'pipeline_completed');
    }

    /**
     * Process staged customers into FA via CREATE_CUSTOMER hook event.
     */
    private function processCustomers(?string $source): ProcessingResult
    {
        $customers = $this->customerDAO->findByStatus('approved', $source);
        if (empty($customers)) {
            return ProcessingResult::success(0, 'no_customers_to_process');
        }

        $processed = 0;
        $errors = [];
        foreach ($customers as $customer) {
            try {
                $faData = $this->buildCustomerFaData($customer);
                $result = $this->invokeCustomerCreation($faData);

                if ($result !== null && !isset($result['error'])) {
                    $debtorNo = (int)($result['fa_debtor_no'] ?? $result['debtor_no'] ?? 0);
                    $this->customerDAO->updateStatus($customer->getId(), 'processed');
                    $this->logDAO->log('customer', $customer->getId(), 'processed', $customer->getSource(), [
                        'fa_debtor_no' => $debtorNo,
                        'module' => 'ksf_FA_Customer',
                    ]);
                    $this->processedIds[] = $customer->getId();
                    $processed++;
                } else {
                    throw new \RuntimeException($result['error'] ?? 'Customer creation returned null');
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Customer %d: %s', $customer->getId(), $e->getMessage());
                $this->customerDAO->updateStatus($customer->getId(), 'failed', $e->getMessage());
                $this->logDAO->log('customer', $customer->getId(), 'failed', $customer->getSource(), [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($errors)) {
            return ProcessingResult::failure($processed, 'customers_processed', $errors);
        }
        return ProcessingResult::success($processed, 'customers_processed');
    }

    /**
     * Process staged transaction records by first ensuring the customer exists,
     * then creating the FA transaction.
     */
    private function processTransactions(?string $source): ProcessingResult
    {
        $transactions = $this->transactionDAO->findByStatus('approved', $source);
        if (empty($transactions)) {
            return ProcessingResult::success(0, 'no_transactions_to_process');
        }

        $processed = 0;
        $errors = [];
        foreach ($transactions as $txn) {
            try {
                $debtorNo = $this->resolveCustomer($txn);
                $faData = $this->buildTransactionFaData($txn, $debtorNo);
                $result = $this->invokeTransactionCreation($faData);

                if ($result !== null && !isset($result['error'])) {
                    $invoiceNo = (int)($result['trans_no'] ?? 0);
                    $this->transactionDAO->updateFaReference($txn->getId(), $invoiceNo, $debtorNo);
                    $this->transactionDAO->updateStatus($txn->getId(), 'processed');
                    $this->logDAO->log('transaction', $txn->getId(), 'processed', $txn->getSource(), [
                        'fa_invoice_no' => $invoiceNo,
                        'fa_debtor_no' => $debtorNo,
                    ]);
                    $this->processedIds[] = $txn->getId();
                    $processed++;
                } else {
                    throw new \RuntimeException($result['error'] ?? 'Transaction creation returned null');
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Transaction %d: %s', $txn->getId(), $e->getMessage());
                $this->transactionDAO->updateStatus($txn->getId(), 'failed', null, $e->getMessage());
                $this->logDAO->log('transaction', $txn->getId(), 'failed', $txn->getSource(), [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($errors)) {
            return ProcessingResult::failure($processed, 'transactions_processed', $errors);
        }
        return ProcessingResult::success($processed, 'transactions_processed');
    }

    /**
     * Process staged payments into FA via CREATE_PAYMENT hook event.
     */
    private function processPayments(?string $source): ProcessingResult
    {
        $payments = $this->paymentDAO->findByStatus('reconciled', $source);
        if (empty($payments)) {
            return ProcessingResult::success(0, 'no_payments_to_process');
        }

        $processed = 0;
        $errors = [];
        foreach ($payments as $payment) {
            try {
                $faData = $this->buildPaymentFaData($payment);
                $result = $this->invokePaymentCreation($faData);

                if ($result !== null && !isset($result['error'])) {
                    $faTransNo = (int)($result['fa_payment_no'] ?? $result['trans_no'] ?? 0);
                    $this->paymentDAO->updateFaReference(
                        $payment->getId(),
                        (int)($result['payment_type'] ?? ST_CUSTPAYMENT),
                        $faTransNo,
                        $result['bank_account'] ?? null
                    );
                    $this->paymentDAO->updateStatus($payment->getId(), 'processed');
                    $this->logDAO->log('payment', $payment->getId(), 'processed', $payment->getSource(), [
                        'fa_trans_no' => $faTransNo,
                        'module' => 'ksf_FA_Payment',
                    ]);
                    $this->processedIds[] = $payment->getId();
                    $processed++;
                } else {
                    throw new \RuntimeException($result['error'] ?? 'Payment creation returned null');
                }
            } catch (\Exception $e) {
                $errors[] = sprintf('Payment %d: %s', $payment->getId(), $e->getMessage());
                $this->paymentDAO->updateStatus($payment->getId(), 'failed', null, $e->getMessage());
                $this->logDAO->log('payment', $payment->getId(), 'failed', $payment->getSource(), [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if (!empty($errors)) {
            return ProcessingResult::failure($processed, 'payments_processed', $errors);
        }
        return ProcessingResult::success($processed, 'payments_processed');
    }

    /**
     * Ensure a customer exists in FA for the given transaction.
     * First checks if already mapped, then tries to find by reference/email,
     * and finally creates a new customer as a fallback.
     */
    private function resolveCustomer($txn): int
    {
        $existingDebtorNo = $txn->getFaDebtorNo();
        if ($existingDebtorNo !== null && $existingDebtorNo > 0) {
            return $existingDebtorNo;
        }

        $customerData = [
            'name' => $txn->getCustomerName() ?? 'Unknown',
            'email' => $txn->getCustomerEmail(),
            'reference' => $txn->getCustomerId(),
        ];
        $result = $this->invokeCustomerCreation($customerData);

        if ($result !== null && !isset($result['error'])) {
            return (int)($result['fa_debtor_no'] ?? $result['debtor_no'] ?? 0);
        }

        throw new \RuntimeException(
            'Could not resolve customer for transaction: ' . ($result['error'] ?? 'unknown error')
        );
    }

    private function buildCustomerFaData($customer): array
    {
        return [
            'name' => $customer->getName() ?? 'Unknown',
            'email' => $customer->getEmail(),
            'phone' => $customer->getPhone(),
            'reference' => $customer->getSourceCustomerId(),
            'address' => implode("\n", array_filter([
                $customer->getAddressLine1(),
                $customer->getAddressLine2(),
                $customer->getCity(),
                $customer->getProvince(),
                $customer->getPostalCode(),
                $customer->getCountry(),
            ])),
        ];
    }

    private function buildTransactionFaData($txn, int $debtorNo): array
    {
        $lineItems = $this->lineItemDAO->findByTransactionId($txn->getId());
        $lineItemData = [];
        foreach ($lineItems as $item) {
            $lineItemData[] = [
                'stock_id' => $item->getSku() ?? '',
                'description' => $item->getDescription() ?? $item->getName(),
                'quantity' => $item->getQuantity(),
                'unit_price' => $item->getUnitPrice(),
                'tax_amount' => $item->getTaxAmount(),
                'tax_percent' => $item->getTaxPercent(),
                'discount_percent' => $item->getDiscountPercent(),
                'total_amount' => $item->getTotalAmount(),
            ];
        }

        return [
            'customer_id' => $debtorNo,
            'reference' => $txn->getSourceOrderId() ?? $txn->getSourceTransactionId(),
            'total_amount' => $txn->getTotalAmount(),
            'tax_amount' => $txn->getTaxAmount(),
            'transaction_date' => $txn->getTransactionDate()
                ? $txn->getTransactionDate()->format('Y-m-d')
                : date('Y-m-d'),
            'source' => $txn->getSource(),
            'source_transaction_id' => $txn->getSourceTransactionId(),
            'source_order_id' => $txn->getSourceOrderId(),
            'line_items' => $lineItemData,
        ];
    }

    private function buildPaymentFaData($payment): array
    {
        return [
            'customer_id' => $payment->getFaTransNo() ?? 0,
            'amount' => $payment->getNetAmount() > 0 ? $payment->getNetAmount() : $payment->getAmount(),
            'reference' => $payment->getReference(),
            'payment_date' => $payment->getPaymentDate()
                ? $payment->getPaymentDate()->format('Y-m-d')
                : date('Y-m-d'),
            'memo' => sprintf(
                '%s payment from %s (ref: %s)',
                $payment->getPaymentMethod() ?? 'Unknown',
                $payment->getSource(),
                $payment->getReference() ?? 'N/A'
            ),
        ];
    }

    /**
     * Invoke customer creation via hook_invoke_first.
     * Falls back to direct service if hook_invoke_first is unavailable.
     */
    private function invokeCustomerCreation(array $data): ?array
    {
        if (function_exists('hook_invoke_first')) {
            $result = hook_invoke_first('CREATE_CUSTOMER', $data);
            if (is_array($result)) {
                return $result;
            }
        }
        return $this->createCustomerDirect($data);
    }

    /**
     * Invoke payment creation via hook_invoke_first.
     * Falls back to direct service if hook_invoke_first is unavailable.
     */
    private function invokePaymentCreation(array $data): ?array
    {
        if (function_exists('hook_invoke_first')) {
            $result = hook_invoke_first('CREATE_PAYMENT', $data);
            if (is_array($result)) {
                return $result;
            }
        }
        return $this->createPaymentDirect($data);
    }

    /**
     * Invoke transaction creation - delegates to the appropriate module.
     */
    private function invokeTransactionCreation(array $data): ?array
    {
        if (function_exists('hook_invoke_first')) {
            $result = hook_invoke_first('CREATE_SALES_INVOICE', $data);
            if (is_array($result)) {
                return $result;
            }
        }
        $data['error'] = 'No module handles CREATE_SALES_INVOICE';
        return $data;
    }

    /**
     * Direct fallback for customer creation when hooks are not available.
     */
    private function createCustomerDirect(array $data): ?array
    {
        if (!class_exists(\Ksfraser\FACustomer\Services\CustomerService::class)) {
            $data['error'] = 'CustomerService not available';
            return $data;
        }
        try {
            $service = new \Ksfraser\FACustomer\Services\CustomerService();
            $dto = $service->createCustomer($data);
            return $dto->toArray();
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
            return $data;
        }
    }

    /**
     * Direct fallback for payment creation when hooks are not available.
     */
    private function createPaymentDirect(array $data): ?array
    {
        if (!class_exists(\Ksfraser\FAPayment\Services\PaymentService::class)) {
            $data['error'] = 'PaymentService not available';
            return $data;
        }
        try {
            $service = new \Ksfraser\FAPayment\Services\PaymentService();
            $dto = $service->createPayment($data);
            return $dto->toArray();
        } catch (\Exception $e) {
            $data['error'] = $e->getMessage();
            return $data;
        }
    }

    public function getProcessedIds(): array
    {
        return $this->processedIds;
    }
}
