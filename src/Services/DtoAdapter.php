<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Services;

use Ksfraser\StagingDto\StagingEntity;
use Ksfraser\StagingDto\StagingTransaction;
use Ksfraser\StagingDto\StagingOrder;
use Ksfraser\StagingDto\StagingInvoice;
use Ksfraser\StagingDto\StagingPayment;
use Ksfraser\StagingDto\StagingRefund;
use Ksfraser\StagingDto\StagingSubscription;
use Ksfraser\StagingDto\StagingCustomer;
use Ksfraser\StagingDto\StagingProduct;
use Ksfraser\StagingDto\StagingProductVariant;
use Ksfraser\StagingDto\StagingCategory;
use Ksfraser\StagingDto\StagingLineItem;
use Ksfraser\StagingDto\StagingExistsQuery;
use Ksfraser\StagingDto\StagingExistsResult;
use ksfraser\FrontAccounting\ImportStaging\Contracts\CustomerRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\TransactionRepositoryInterface;
use ksfraser\FrontAccounting\ImportStaging\Contracts\PaymentRepositoryInterface;

/**
 * Adapter converting ksfraser/staging-dto DTOs to arrays for StagingService.
 *
 * Dispatches on DTO type (order, invoice, payment, refund, subscription,
 * customer, product, product_variant, category) and routes to the
 * appropriate stagingOrUpdate method. Throws InvalidArgumentException for
 * unsupported DTO types.
 *
 * @since 1.1.0
 * @BABOK Related: FR-ISU-009-001 DTO-based staging dispatch
 * @BABOK Related: FR-ISU-009-002 stageEntity polymorphic entry point
 * @BABOK Related: FR-ISU-005-007 stagingExists duplicate check
 * @BABOK Related: NFR-ISU-009 Adapter pattern
 */
class DtoAdapter
{
    private StagingService $stagingService;
    private TransactionRepositoryInterface $transactionDAO;
    private CustomerRepositoryInterface $customerDAO;
    private PaymentRepositoryInterface $paymentDAO;

    /**
     * @param StagingService $stagingService
     * @param TransactionRepositoryInterface $transactionDAO
     * @param CustomerRepositoryInterface $customerDAO
     * @param PaymentRepositoryInterface $paymentDAO
     * @since 1.1.0
     * @BABOK Related: FR-ISU-009-001 constructor
     */
    public function __construct(
        StagingService $stagingService,
        TransactionRepositoryInterface $transactionDAO,
        CustomerRepositoryInterface $customerDAO,
        PaymentRepositoryInterface $paymentDAO
    ) {
        $this->stagingService = $stagingService;
        $this->transactionDAO = $transactionDAO;
        $this->customerDAO = $customerDAO;
        $this->paymentDAO = $paymentDAO;
    }

    /**
     * Stage any DTO subclass via StagingService.
     *
     * @param StagingEntity $dto The DTO to stage
     * @return StagingExistsResult Existence result after staging
     * @throws \InvalidArgumentException If DTO type not supported
     */
    /**
     * Stage any DTO subclass via StagingService.
     *
     * Routes to type-specific staging method based on DTO class.
     *
     * @param StagingEntity $dto The DTO to stage
     * @return StagingExistsResult Existence result after staging
     * @throws \InvalidArgumentException If DTO type not supported
     * @since 1.1.0
     * @BABOK Related: FR-ISU-009-002 stageEntity
     */
    public function stageEntity(StagingEntity $dto): StagingExistsResult
    {
        if ($dto instanceof StagingOrder) {
            return $this->stageOrder($dto);
        }
        if ($dto instanceof StagingInvoice) {
            return $this->stageInvoice($dto);
        }
        if ($dto instanceof StagingPayment) {
            return $this->stagePaymentDto($dto);
        }
        if ($dto instanceof StagingRefund) {
            return $this->stageRefund($dto);
        }
        if ($dto instanceof StagingSubscription) {
            return $this->stageSubscription($dto);
        }
        if ($dto instanceof StagingCustomer) {
            return $this->stageCustomerDto($dto);
        }
        if ($dto instanceof StagingProduct) {
            return $this->stageProduct($dto);
        }
        if ($dto instanceof StagingProductVariant) {
            return $this->stageProductVariant($dto);
        }
        if ($dto instanceof StagingCategory) {
            return $this->stageCategory($dto);
        }

        throw new \InvalidArgumentException(
            'Unsupported DTO type: ' . get_class($dto)
        );
    }

    /**
     * Check staging existence via query DTO.
     *
     * @param StagingExistsQuery $query The query
     * @return StagingExistsResult Existence result
     */
    /**
     * Check staging existence via query DTO.
     *
     * Routes to type-specific existence check based on entity type.
     *
     * @param StagingExistsQuery $query The query
     * @return StagingExistsResult Existence result
     * @since 1.1.0
     * @BABOK Related: FR-ISU-005-007 stagingExists
     */
    public function stagingExists(StagingExistsQuery $query): StagingExistsResult
    {
        $source = $query->getSource();
        $sourceId = $query->getSourceId();
        $entityType = $query->getEntityType();

        switch ($entityType) {
            case 'order':
            case 'invoice':
            case 'transaction':
                return $this->checkTransactionExists($source, $sourceId);
            case 'customer':
                return $this->checkCustomerExists($source, $sourceId);
            case 'payment':
                return $this->checkPaymentExists($source, $sourceId);
            default:
                return new StagingExistsResult(false, 0, '', 'Unknown entity type: ' . $entityType);
        }
    }

    /**
     * Stage a StagingOrder DTO via stageOrUpdateTransaction.
     *
     * @param StagingOrder $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageOrder
     */
    private function stageOrder(StagingOrder $dto): StagingExistsResult
    {
        $data = $this->transactionToArray($dto);
        $data['line_items'] = $this->lineItemsToArray($dto->getLineItems());
        $data['customer_source_id'] = $dto->getCustomerSourceId();
        $data['billing_address'] = $dto->getBillingAddress();
        $data['shipping_address'] = $dto->getShippingAddress();

        try {
            $result = $this->stagingService->stageOrUpdateTransaction($data, $dto->getSource());
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Order staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingInvoice DTO via stageOrUpdateTransaction.
     *
     * @param StagingInvoice $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageInvoice
     */
    private function stageInvoice(StagingInvoice $dto): StagingExistsResult
    {
        $data = $this->transactionToArray($dto);
        $data['line_items'] = $this->lineItemsToArray($dto->getLineItems());
        $data['customer_source_id'] = $dto->getCustomerSourceId();
        $data['due_date'] = $dto->getDueDate();

        try {
            $result = $this->stagingService->stageOrUpdateTransaction($data, $dto->getSource());
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Invoice staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingPayment DTO via stageOrUpdatePayment.
     *
     * @param StagingPayment $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-003-001 stagePaymentDto
     */
    private function stagePaymentDto(StagingPayment $dto): StagingExistsResult
    {
        $data = [
            'source' => $dto->getSource(),
            'source_payment_id' => $dto->getSourceId(),
            'amount' => $dto->getAmount(),
            'currency' => $dto->getCurrency(),
            'status' => $dto->getStatus(),
            'payment_method' => $dto->getPaymentMethod(),
            'payment_date' => $dto->getCreatedAt(),
            'source_transaction_id' => $dto->getTransactionSourceId(),
            'invoice_source_id' => $dto->getInvoiceSourceId(),
        ];

        try {
            $result = $this->stagingService->stageOrUpdatePayment($data, $dto->getSource());
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Payment staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingRefund DTO via stageOrUpdateTransaction.
     *
     * @param StagingRefund $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageRefund
     */
    private function stageRefund(StagingRefund $dto): StagingExistsResult
    {
        $data = $this->transactionToArray($dto);
        $data['source_transaction_id'] = $dto->getTransactionSourceId();
        $data['refund_reason'] = $dto->getReason();
        $data['transaction_type'] = 'refund';

        try {
            $result = $this->stagingService->stageOrUpdateTransaction($data, $dto->getSource());
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Refund staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingSubscription DTO via stageOrUpdateTransaction.
     *
     * @param StagingSubscription $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageSubscription
     */
    private function stageSubscription(StagingSubscription $dto): StagingExistsResult
    {
        $data = $this->transactionToArray($dto);
        $data['line_items'] = $this->lineItemsToArray($dto->getLineItems());
        $data['frequency'] = $dto->getFrequency();
        $data['next_billing_date'] = $dto->getNextBillingDate();
        $data['transaction_type'] = 'subscription';

        try {
            $result = $this->stagingService->stageOrUpdateTransaction($data, $dto->getSource());
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Subscription staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingCustomer DTO via stageOrUpdateCustomer.
     *
     * @param StagingCustomer $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-001-001 stageCustomerDto
     */
    private function stageCustomerDto(StagingCustomer $dto): StagingExistsResult
    {
        $name = trim(implode(' ', array_filter([
            $dto->getFirstName(),
            $dto->getLastName(),
            $dto->getCompany(),
        ], fn($v) => $v !== '')));
        $data = [
            'source' => $dto->getSource(),
            'source_customer_id' => $dto->getSourceId(),
            'name' => $name,
            'first_name' => $dto->getFirstName(),
            'last_name' => $dto->getLastName(),
            'email' => $dto->getEmail(),
            'phone' => $dto->getPhone(),
            'company' => $dto->getCompany(),
            'addresses' => $dto->getAddresses(),
        ];

        try {
            $result = $this->stagingService->stageOrUpdateCustomer($data, $dto->getSource());
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Customer staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingProduct DTO via stageOrUpdateTransaction.
     *
     * @param StagingProduct $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageProduct
     */
    private function stageProduct(StagingProduct $dto): StagingExistsResult
    {
        $data = [
            'source' => $dto->getSource(),
            'source_product_id' => $dto->getSourceId(),
            'sku' => $dto->getSku(),
            'name' => $dto->getName(),
            'description' => $dto->getDescription(),
            'price' => $dto->getPrice(),
            'weight' => $dto->getWeight(),
            'categories' => $dto->getCategories(),
            'images' => $dto->getImages(),
        ];

        try {
            $result = $this->stagingService->stageOrUpdateTransaction(
                array_merge($data, ['total_amount' => $dto->getPrice()]),
                $dto->getSource()
            );
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Product staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingProductVariant DTO via stageOrUpdateTransaction.
     *
     * @param StagingProductVariant $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageProductVariant
     */
    private function stageProductVariant(StagingProductVariant $dto): StagingExistsResult
    {
        $data = [
            'source' => $dto->getSource(),
            'source_product_id' => $dto->getSourceId(),
            'parent_source_id' => $dto->getProductSourceId(),
            'sku' => $dto->getSku(),
            'attributes' => $dto->getAttributes(),
            'price' => $dto->getPrice(),
            'stock' => $dto->getStock(),
        ];

        try {
            $result = $this->stagingService->stageOrUpdateTransaction(
                array_merge($data, ['total_amount' => $dto->getPrice()]),
                $dto->getSource()
            );
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Product variant staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Stage a StagingCategory DTO via stageOrUpdateTransaction.
     *
     * @param StagingCategory $dto
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 stageCategory
     */
    private function stageCategory(StagingCategory $dto): StagingExistsResult
    {
        $data = [
            'source' => $dto->getSource(),
            'source_category_id' => $dto->getSourceId(),
            'name' => $dto->getName(),
            'parent_source_id' => $dto->getParentSourceId(),
            'description' => $dto->getDescription(),
        ];

        try {
            $result = $this->stagingService->stageOrUpdateTransaction(
                array_merge($data, ['total_amount' => 0]),
                $dto->getSource()
            );
            return new StagingExistsResult(
                true,
                $result->getId() ?? 0,
                $result->getStatus() ?? 'staged',
                'Category staged successfully'
            );
        } catch (\Exception $e) {
            return new StagingExistsResult(false, 0, 'error', $e->getMessage());
        }
    }

    /**
     * Convert a StagingTransaction DTO to a transaction data array.
     *
     * @param StagingTransaction $dto
     * @return array
     * @since 1.1.0
     * @BABOK Related: FR-ISU-002-001 transactionToArray
     */
    private function transactionToArray(StagingTransaction $dto): array
    {
        return [
            'source' => $dto->getSource(),
            'source_transaction_id' => $dto->getSourceId(),
            'total_amount' => $dto->getAmount(),
            'currency' => $dto->getCurrency(),
            'status' => $dto->getStatus(),
            'payment_method' => $dto->getPaymentMethod(),
            'transaction_date' => $dto->getCreatedAt(),
        ];
    }

    /**
     * Convert StagingLineItem DTOs to an array of line item data.
     *
     * @param array $lineItems
     * @return array
     * @since 1.1.0
     * @BABOK Related: FR-ISU-004-001 lineItemsToArray
     */
    private function lineItemsToArray(array $lineItems): array
    {
        $result = [];
        foreach ($lineItems as $item) {
            if ($item instanceof StagingLineItem) {
                $result[] = [
                    'source_line_item_id' => $item->getSourceId(),
                    'sku' => $item->getSku(),
                    'name' => $item->getName(),
                    'description' => $item->getDescription(),
                    'quantity' => $item->getQuantity(),
                    'unit_price' => $item->getUnitPrice(),
                    'discount' => $item->getDiscount(),
                    'tax' => $item->getTax(),
                ];
            } elseif (is_array($item)) {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Check whether a transaction exists for source + source_id.
     *
     * @param string $source
     * @param string $sourceId
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-005-007 checkTransactionExists
     */
    private function checkTransactionExists(string $source, string $sourceId): StagingExistsResult
    {
        $existing = $this->transactionDAO->findBySource($source, $sourceId);
        if ($existing) {
            return new StagingExistsResult(
                true,
                $existing->getId(),
                $existing->getStatus() ?? '',
                'Transaction found'
            );
        }
        return new StagingExistsResult(false, 0, '', 'Transaction not found');
    }

    /**
     * Check whether a customer exists for source + source_id.
     *
     * @param string $source
     * @param string $sourceId
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-005-007 checkCustomerExists
     */
    private function checkCustomerExists(string $source, string $sourceId): StagingExistsResult
    {
        $existing = $this->customerDAO->findBySource($source, $sourceId);
        if ($existing) {
            return new StagingExistsResult(
                true,
                $existing->getId(),
                $existing->getStatus() ?? '',
                'Customer found'
            );
        }
        return new StagingExistsResult(false, 0, '', 'Customer not found');
    }

    /**
     * Check whether a payment exists for source + source_id.
     *
     * @param string $source
     * @param string $sourceId
     * @return StagingExistsResult
     * @since 1.1.0
     * @BABOK Related: FR-ISU-005-007 checkPaymentExists
     */
    private function checkPaymentExists(string $source, string $sourceId): StagingExistsResult
    {
        $existing = $this->paymentDAO->findBySource($source, $sourceId);
        if ($existing) {
            return new StagingExistsResult(
                true,
                $existing->getId(),
                $existing->getStatus() ?? '',
                'Payment found'
            );
        }
        return new StagingExistsResult(false, 0, '', 'Payment not found');
    }
}
