<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

/**
 * Data model for a unified staged transaction from any import source.
 *
 * Carries normalized transaction fields (total_amount, currency, dates,
 * customer info) plus FA invoice/debtor mapping and match confidence.
 * Status lifecycle: staged → validated → matched → processed → failed.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-002-001 staging_transactions table
 * @BABOK Related: FR-ISU-002-002 normalized common fields
 * @BABOK Related: FR-ISU-002-003 source column
 * @BABOK Related: FR-ISU-005-001 source_transaction_id duplicate detection
 * @BABOK Related: FR-ISU-009-001 matching input
 * @BABOK Related: FR-ISU-012-001 fa_invoice_no / fa_debtor_no mapping
 * @BABOK Related: NFR-ISU-003 TB_PREF convention
 */
class StagingTransaction
{
    private ?int $id;
    private string $source;
    private ?string $sourceTransactionId;
    private ?string $sourceOrderId;
    private ?string $sourcePaymentId;
    private ?\DateTimeInterface $transactionDate;
    private float $totalAmount;
    private float $taxAmount;
    private float $tipAmount;
    private float $discountAmount;
    private float $shippingAmount;
    private string $currency;
    private ?string $customerName;
    private ?string $customerEmail;
    private ?string $customerId;
    private ?string $rawJson;
    private string $status;
    private ?float $matchConfidence;
    private ?int $faInvoiceNo;
    private ?int $faDebtorNo;
    private ?string $errorLog;
    private ?\DateTimeInterface $sourceUpdatedAt;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    /**
     * @param string $source
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-003 source field
     */
    public function __construct(string $source)
    {
        $this->source = $source;
        $this->id = null;
        $this->status = 'staged';
        $this->currency = 'CAD';
        $this->totalAmount = 0.0;
        $this->taxAmount = 0.0;
        $this->tipAmount = 0.0;
        $this->discountAmount = 0.0;
        $this->shippingAmount = 0.0;
        $this->sourceTransactionId = null;
        $this->sourceOrderId = null;
        $this->sourcePaymentId = null;
        $this->transactionDate = null;
        $this->customerName = null;
        $this->customerEmail = null;
        $this->customerId = null;
        $this->rawJson = null;
        $this->matchConfidence = null;
        $this->faInvoiceNo = null;
        $this->faDebtorNo = null;
        $this->errorLog = null;
        $this->sourceUpdatedAt = null;
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 id field
     */
    public function getId(): ?int { return $this->id; }
    /**
     * @param int $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 id field
     */
    public function setId(int $id): void { $this->id = $id; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-003 source field
     */
    public function getSource(): string { return $this->source; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 source_transaction_id field
     */
    public function getSourceTransactionId(): ?string { return $this->sourceTransactionId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-001 source_transaction_id field
     */
    public function setSourceTransactionId(?string $id): void { $this->sourceTransactionId = $id; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 source_order_id field
     */
    public function getSourceOrderId(): ?string { return $this->sourceOrderId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 source_order_id field
     */
    public function setSourceOrderId(?string $id): void { $this->sourceOrderId = $id; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 source_payment_id field
     */
    public function getSourcePaymentId(): ?string { return $this->sourcePaymentId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 source_payment_id field
     */
    public function setSourcePaymentId(?string $id): void { $this->sourcePaymentId = $id; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 transaction_date field
     */
    public function getTransactionDate(): ?\DateTimeInterface { return $this->transactionDate; }
    /**
     * @param ?\DateTimeInterface $date
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 transaction_date field
     */
    public function setTransactionDate(?\DateTimeInterface $date): void { $this->transactionDate = $date; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 total_amount field
     */
    public function getTotalAmount(): float { return $this->totalAmount; }
    /**
     * @param float $amount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 total_amount field
     */
    public function setTotalAmount(float $amount): void { $this->totalAmount = $amount; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 tax_amount field
     */
    public function getTaxAmount(): float { return $this->taxAmount; }
    /**
     * @param float $amount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 tax_amount field
     */
    public function setTaxAmount(float $amount): void { $this->taxAmount = $amount; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 tip_amount field
     */
    public function getTipAmount(): float { return $this->tipAmount; }
    /**
     * @param float $amount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 tip_amount field
     */
    public function setTipAmount(float $amount): void { $this->tipAmount = $amount; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 discount_amount field
     */
    public function getDiscountAmount(): float { return $this->discountAmount; }
    /**
     * @param float $amount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 discount_amount field
     */
    public function setDiscountAmount(float $amount): void { $this->discountAmount = $amount; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 shipping_amount field
     */
    public function getShippingAmount(): float { return $this->shippingAmount; }
    /**
     * @param float $amount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 shipping_amount field
     */
    public function setShippingAmount(float $amount): void { $this->shippingAmount = $amount; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 currency field
     */
    public function getCurrency(): string { return $this->currency; }
    /**
     * @param string $currency
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 currency field
     */
    public function setCurrency(string $currency): void { $this->currency = $currency; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 customer_name field
     */
    public function getCustomerName(): ?string { return $this->customerName; }
    /**
     * @param ?string $name
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 customer_name field
     */
    public function setCustomerName(?string $name): void { $this->customerName = $name; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 customer_email field
     */
    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    /**
     * @param ?string $email
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 customer_email field
     */
    public function setCustomerEmail(?string $email): void { $this->customerEmail = $email; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 customer_id field
     */
    public function getCustomerId(): ?string { return $this->customerId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 customer_id field
     */
    public function setCustomerId(?string $id): void { $this->customerId = $id; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 raw_json field
     */
    public function getRawJson(): ?string { return $this->rawJson; }
    /**
     * @param ?string $json
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-002 raw_json field
     */
    public function setRawJson(?string $json): void { $this->rawJson = $json; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 status field
     */
    public function getStatus(): string { return $this->status; }
    /**
     * @param string $status
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 status field
     */
    public function setStatus(string $status): void { $this->status = $status; }

    /**
     * @return ?float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-001 match_confidence field
     */
    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    /**
     * @param ?float $confidence
     * @since 2.0.0
     * @BABOK Related: FR-ISU-009-001 match_confidence field
     */
    public function setMatchConfidence(?float $confidence): void { $this->matchConfidence = $confidence; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 fa_invoice_no field
     */
    public function getFaInvoiceNo(): ?int { return $this->faInvoiceNo; }
    /**
     * @param ?int $no
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 fa_invoice_no field
     */
    public function setFaInvoiceNo(?int $no): void { $this->faInvoiceNo = $no; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 fa_debtor_no field
     */
    public function getFaDebtorNo(): ?int { return $this->faDebtorNo; }
    /**
     * @param ?int $no
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-001 fa_debtor_no field
     */
    public function setFaDebtorNo(?int $no): void { $this->faDebtorNo = $no; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 error_log field
     */
    public function getErrorLog(): ?string { return $this->errorLog; }
    /**
     * @param ?string $log
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 error_log field
     */
    public function setErrorLog(?string $log): void { $this->errorLog = $log; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 created_at field
     */
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 created_at field
     */
    public function setCreatedAt(?\DateTimeInterface $dt): void { $this->createdAt = $dt; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updated_at field
     */
    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-016-001 updated_at field
     */
    public function setUpdatedAt(?\DateTimeInterface $dt): void { $this->updatedAt = $dt; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-003 source_updated_at field
     */
    public function getSourceUpdatedAt(): ?\DateTimeInterface { return $this->sourceUpdatedAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-003 source_updated_at field
     */
    public function setSourceUpdatedAt(?\DateTimeInterface $dt): void { $this->sourceUpdatedAt = $dt; }

    /**
     * Serialize the transaction to an associative array for DAO persistence.
     *
     * @return array<string, mixed>
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 full record serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_transaction_id' => $this->sourceTransactionId,
            'source_order_id' => $this->sourceOrderId,
            'source_payment_id' => $this->sourcePaymentId,
            'transaction_date' => $this->transactionDate ? $this->transactionDate->format('Y-m-d') : null,
            'total_amount' => $this->totalAmount,
            'tax_amount' => $this->taxAmount,
            'tip_amount' => $this->tipAmount,
            'discount_amount' => $this->discountAmount,
            'shipping_amount' => $this->shippingAmount,
            'currency' => $this->currency,
            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_id' => $this->customerId,
            'raw_json' => $this->rawJson,
            'status' => $this->status,
            'match_confidence' => $this->matchConfidence,
            'fa_invoice_no' => $this->faInvoiceNo,
            'fa_debtor_no' => $this->faDebtorNo,
            'error_log' => $this->errorLog,
            'source_updated_at' => $this->sourceUpdatedAt ? $this->sourceUpdatedAt->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Hydrate a transaction from an associative array (DAO result set).
     *
     * @param array $data Row data from the database
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-002-001 full record hydration
     */
    public static function fromArray(array $data): self
    {
        $txn = new self($data['source'] ?? 'unknown');
        if (isset($data['id'])) $txn->setId((int)$data['id']);
        if (isset($data['source_transaction_id'])) $txn->setSourceTransactionId($data['source_transaction_id']);
        if (isset($data['source_order_id'])) $txn->setSourceOrderId($data['source_order_id']);
        if (isset($data['source_payment_id'])) $txn->setSourcePaymentId($data['source_payment_id']);
        if (isset($data['transaction_date'])) $txn->setTransactionDate(new \DateTimeImmutable($data['transaction_date']));
        if (isset($data['total_amount'])) $txn->setTotalAmount((float)$data['total_amount']);
        if (isset($data['tax_amount'])) $txn->setTaxAmount((float)$data['tax_amount']);
        if (isset($data['tip_amount'])) $txn->setTipAmount((float)$data['tip_amount']);
        if (isset($data['discount_amount'])) $txn->setDiscountAmount((float)$data['discount_amount']);
        if (isset($data['shipping_amount'])) $txn->setShippingAmount((float)$data['shipping_amount']);
        if (isset($data['currency'])) $txn->setCurrency($data['currency']);
        if (isset($data['customer_name'])) $txn->setCustomerName($data['customer_name']);
        if (isset($data['customer_email'])) $txn->setCustomerEmail($data['customer_email']);
        if (isset($data['customer_id'])) $txn->setCustomerId($data['customer_id']);
        if (isset($data['raw_json'])) $txn->setRawJson($data['raw_json']);
        if (isset($data['status'])) $txn->setStatus($data['status']);
        if (isset($data['match_confidence'])) $txn->setMatchConfidence((float)$data['match_confidence']);
        if (isset($data['fa_invoice_no'])) $txn->setFaInvoiceNo((int)$data['fa_invoice_no']);
        if (isset($data['fa_debtor_no'])) $txn->setFaDebtorNo((int)$data['fa_debtor_no']);
        if (isset($data['error_log'])) $txn->setErrorLog($data['error_log']);
        if (isset($data['source_updated_at'])) $txn->setSourceUpdatedAt(new \DateTimeImmutable($data['source_updated_at']));
        return $txn;
    }
}
