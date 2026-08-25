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
 * @package Ksfraser\FrontAccounting\ImportStaging\Services
 * @since 1.1.0
 */
class DtoAdapter
{
    private StagingService $stagingService;
    private TransactionRepositoryInterface $transactionDAO;
    private CustomerRepositoryInterface $customerDAO;
    private PaymentRepositoryInterface $paymentDAO;

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

    private function stageCustomerDto(StagingCustomer $dto): StagingExistsResult
    {
        $data = [
            'source' => $dto->getSource(),
            'source_customer_id' => $dto->getSourceId(),
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
