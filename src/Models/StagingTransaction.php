<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

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

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getSource(): string { return $this->source; }

    public function getSourceTransactionId(): ?string { return $this->sourceTransactionId; }
    public function setSourceTransactionId(?string $id): void { $this->sourceTransactionId = $id; }

    public function getSourceOrderId(): ?string { return $this->sourceOrderId; }
    public function setSourceOrderId(?string $id): void { $this->sourceOrderId = $id; }

    public function getSourcePaymentId(): ?string { return $this->sourcePaymentId; }
    public function setSourcePaymentId(?string $id): void { $this->sourcePaymentId = $id; }

    public function getTransactionDate(): ?\DateTimeInterface { return $this->transactionDate; }
    public function setTransactionDate(?\DateTimeInterface $date): void { $this->transactionDate = $date; }

    public function getTotalAmount(): float { return $this->totalAmount; }
    public function setTotalAmount(float $amount): void { $this->totalAmount = $amount; }

    public function getTaxAmount(): float { return $this->taxAmount; }
    public function setTaxAmount(float $amount): void { $this->taxAmount = $amount; }

    public function getTipAmount(): float { return $this->tipAmount; }
    public function setTipAmount(float $amount): void { $this->tipAmount = $amount; }

    public function getDiscountAmount(): float { return $this->discountAmount; }
    public function setDiscountAmount(float $amount): void { $this->discountAmount = $amount; }

    public function getShippingAmount(): float { return $this->shippingAmount; }
    public function setShippingAmount(float $amount): void { $this->shippingAmount = $amount; }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }

    public function getCustomerName(): ?string { return $this->customerName; }
    public function setCustomerName(?string $name): void { $this->customerName = $name; }

    public function getCustomerEmail(): ?string { return $this->customerEmail; }
    public function setCustomerEmail(?string $email): void { $this->customerEmail = $email; }

    public function getCustomerId(): ?string { return $this->customerId; }
    public function setCustomerId(?string $id): void { $this->customerId = $id; }

    public function getRawJson(): ?string { return $this->rawJson; }
    public function setRawJson(?string $json): void { $this->rawJson = $json; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    public function setMatchConfidence(?float $confidence): void { $this->matchConfidence = $confidence; }

    public function getFaInvoiceNo(): ?int { return $this->faInvoiceNo; }
    public function setFaInvoiceNo(?int $no): void { $this->faInvoiceNo = $no; }

    public function getFaDebtorNo(): ?int { return $this->faDebtorNo; }
    public function setFaDebtorNo(?int $no): void { $this->faDebtorNo = $no; }

    public function getErrorLog(): ?string { return $this->errorLog; }
    public function setErrorLog(?string $log): void { $this->errorLog = $log; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $dt): void { $this->createdAt = $dt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $dt): void { $this->updatedAt = $dt; }

    public function getSourceUpdatedAt(): ?\DateTimeInterface { return $this->sourceUpdatedAt; }
    public function setSourceUpdatedAt(?\DateTimeInterface $dt): void { $this->sourceUpdatedAt = $dt; }

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
