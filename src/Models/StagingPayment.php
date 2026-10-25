<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

/**
 * Data model for a unified staged payment from any import source.
 *
 * Carries payment fields (amount, fee, net_amount, card details) plus FA
 * transaction mapping and reconciliation status. Net amount auto-calculated
 * as amount − fee when not provided.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-003-001 staging_payments table
 * @BABOK Related: FR-ISU-003-002 tenders, fees, net amounts, card details
 * @BABOK Related: FR-ISU-003-003 net_amount = amount − fee auto-calculated
 * @BABOK Related: FR-ISU-005-002 source_payment_id duplicate detection
 * @BABOK Related: FR-ISU-013-001 reconciliation input
 * @BABOK Related: FR-ISU-013-002 fa_trans_type / fa_trans_no mapping
 * @BABOK Related: NFR-ISU-003 TB_PREF convention
 */
class StagingPayment
{
    private ?int $id;
    private string $source;
    private ?string $sourcePaymentId;
    private ?string $sourceTransactionId;
    private ?int $stagingTransactionId;
    private float $amount;
    private string $currency;
    private float $fee;
    private float $netAmount;
    private ?string $paymentMethod;
    private ?\DateTimeInterface $paymentDate;
    private ?string $reference;
    private ?string $cardBrand;
    private ?string $panSuffix;
    private ?string $cardEntryMethod;
    private ?string $rawJson;
    private string $status;
    private ?float $matchConfidence;
    private ?int $faTransType;
    private ?int $faTransNo;
    private ?string $faBankAccount;
    private ?string $errorLog;
    private ?\DateTimeInterface $sourceUpdatedAt;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    /**
     * @param string $source
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 source field
     */
    public function __construct(string $source)
    {
        $this->source = $source;
        $this->id = null;
        $this->status = 'staged';
        $this->currency = 'CAD';
        $this->amount = 0.0;
        $this->fee = 0.0;
        $this->netAmount = 0.0;
        $this->paymentMethod = null;
        $this->paymentDate = null;
        $this->reference = null;
        $this->cardBrand = null;
        $this->panSuffix = null;
        $this->cardEntryMethod = null;
        $this->rawJson = null;
        $this->matchConfidence = null;
        $this->faTransType = null;
        $this->faTransNo = null;
        $this->faBankAccount = null;
        $this->errorLog = null;
        $this->sourceUpdatedAt = null;
        $this->createdAt = null;
        $this->updatedAt = null;
        $this->sourcePaymentId = null;
        $this->sourceTransactionId = null;
        $this->stagingTransactionId = null;
    }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 id field
     */
    public function getId(): ?int { return $this->id; }
    /**
     * @param int $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 id field
     */
    public function setId(int $id): void { $this->id = $id; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 source field
     */
    public function getSource(): string { return $this->source; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-002 source_payment_id field
     */
    public function getSourcePaymentId(): ?string { return $this->sourcePaymentId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-002 source_payment_id field
     */
    public function setSourcePaymentId(?string $id): void { $this->sourcePaymentId = $id; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 source_transaction_id field
     */
    public function getSourceTransactionId(): ?string { return $this->sourceTransactionId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 source_transaction_id field
     */
    public function setSourceTransactionId(?string $id): void { $this->sourceTransactionId = $id; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 staging_transaction_id field
     */
    public function getStagingTransactionId(): ?int { return $this->stagingTransactionId; }
    /**
     * @param ?int $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 staging_transaction_id field
     */
    public function setStagingTransactionId(?int $id): void { $this->stagingTransactionId = $id; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 amount field
     */
    public function getAmount(): float { return $this->amount; }
    /**
     * @param float $amount
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 amount field
     */
    public function setAmount(float $amount): void { $this->amount = $amount; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 currency field
     */
    public function getCurrency(): string { return $this->currency; }
    /**
     * @param string $currency
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 currency field
     */
    public function setCurrency(string $currency): void { $this->currency = $currency; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 fee field
     */
    public function getFee(): float { return $this->fee; }
    /**
     * @param float $fee
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 fee field
     */
    public function setFee(float $fee): void { $this->fee = $fee; }

    /**
     * @return float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-003 net_amount field
     */
    public function getNetAmount(): float { return $this->netAmount; }
    /**
     * @param float $net
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-003 net_amount field
     */
    public function setNetAmount(float $net): void { $this->netAmount = $net; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 payment_method field
     */
    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    /**
     * @param ?string $method
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 payment_method field
     */
    public function setPaymentMethod(?string $method): void { $this->paymentMethod = $method; }

    /**
     * @return ?\DateTimeInterface
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 payment_date field
     */
    public function getPaymentDate(): ?\DateTimeInterface { return $this->paymentDate; }
    /**
     * @param ?\DateTimeInterface $date
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 payment_date field
     */
    public function setPaymentDate(?\DateTimeInterface $date): void { $this->paymentDate = $date; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 reference field
     */
    public function getReference(): ?string { return $this->reference; }
    /**
     * @param ?string $ref
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 reference field
     */
    public function setReference(?string $ref): void { $this->reference = $ref; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 card_brand field
     */
    public function getCardBrand(): ?string { return $this->cardBrand; }
    /**
     * @param ?string $brand
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 card_brand field
     */
    public function setCardBrand(?string $brand): void { $this->cardBrand = $brand; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 pan_suffix field
     */
    public function getPanSuffix(): ?string { return $this->panSuffix; }
    /**
     * @param ?string $suffix
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 pan_suffix field
     */
    public function setPanSuffix(?string $suffix): void { $this->panSuffix = $suffix; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 card_entry_method field
     */
    public function getCardEntryMethod(): ?string { return $this->cardEntryMethod; }
    /**
     * @param ?string $method
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 card_entry_method field
     */
    public function setCardEntryMethod(?string $method): void { $this->cardEntryMethod = $method; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 raw_json field
     */
    public function getRawJson(): ?string { return $this->rawJson; }
    /**
     * @param ?string $json
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-002 raw_json field
     */
    public function setRawJson(?string $json): void { $this->rawJson = $json; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 status field
     */
    public function getStatus(): string { return $this->status; }
    /**
     * @param string $status
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 status field
     */
    public function setStatus(string $status): void { $this->status = $status; }

    /**
     * @return ?float
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_confidence field
     */
    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    /**
     * @param ?float $confidence
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 match_confidence field
     */
    public function setMatchConfidence(?float $confidence): void { $this->matchConfidence = $confidence; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_type field
     */
    public function getFaTransType(): ?int { return $this->faTransType; }
    /**
     * @param ?int $type
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_type field
     */
    public function setFaTransType(?int $type): void { $this->faTransType = $type; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_no field
     */
    public function getFaTransNo(): ?int { return $this->faTransNo; }
    /**
     * @param ?int $no
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_trans_no field
     */
    public function setFaTransNo(?int $no): void { $this->faTransNo = $no; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_bank_account field
     */
    public function getFaBankAccount(): ?string { return $this->faBankAccount; }
    /**
     * @param ?string $acct
     * @since 2.0.0
     * @BABOK Related: FR-ISU-013-002 fa_bank_account field
     */
    public function setFaBankAccount(?string $acct): void { $this->faBankAccount = $acct; }

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
     * @BABOK Related: FR-ISU-003-001 source_updated_at field
     */
    public function getSourceUpdatedAt(): ?\DateTimeInterface { return $this->sourceUpdatedAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 source_updated_at field
     */
    public function setSourceUpdatedAt(?\DateTimeInterface $dt): void { $this->sourceUpdatedAt = $dt; }

    /**
     * Serialize the payment to an associative array for DAO persistence.
     *
     * @return array<string, mixed>
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 full record serialization
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_payment_id' => $this->sourcePaymentId,
            'source_transaction_id' => $this->sourceTransactionId,
            'staging_transaction_id' => $this->stagingTransactionId,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'fee' => $this->fee,
            'net_amount' => $this->netAmount,
            'payment_method' => $this->paymentMethod,
            'payment_date' => $this->paymentDate ? $this->paymentDate->format('Y-m-d') : null,
            'reference' => $this->reference,
            'card_brand' => $this->cardBrand,
            'pan_suffix' => $this->panSuffix,
            'card_entry_method' => $this->cardEntryMethod,
            'raw_json' => $this->rawJson,
            'status' => $this->status,
            'match_confidence' => $this->matchConfidence,
            'fa_trans_type' => $this->faTransType,
            'fa_trans_no' => $this->faTransNo,
            'fa_bank_account' => $this->faBankAccount,
            'error_log' => $this->errorLog,
            'source_updated_at' => $this->sourceUpdatedAt ? $this->sourceUpdatedAt->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Hydrate a payment from an associative array (DAO result set).
     *
     * @param array $data Row data from the database
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-003-001 full record hydration
     */
    public static function fromArray(array $data): self
    {
        $payment = new self($data['source'] ?? 'unknown');
        if (isset($data['id'])) $payment->setId((int)$data['id']);
        if (isset($data['source_payment_id'])) $payment->setSourcePaymentId($data['source_payment_id']);
        if (isset($data['source_transaction_id'])) $payment->setSourceTransactionId($data['source_transaction_id']);
        if (isset($data['staging_transaction_id'])) $payment->setStagingTransactionId((int)$data['staging_transaction_id']);
        if (isset($data['amount'])) $payment->setAmount((float)$data['amount']);
        if (isset($data['currency'])) $payment->setCurrency($data['currency']);
        if (isset($data['fee'])) $payment->setFee((float)$data['fee']);
        if (isset($data['net_amount'])) $payment->setNetAmount((float)$data['net_amount']);
        if (isset($data['payment_method'])) $payment->setPaymentMethod($data['payment_method']);
        if (isset($data['payment_date'])) $payment->setPaymentDate(new \DateTimeImmutable($data['payment_date']));
        if (isset($data['reference'])) $payment->setReference($data['reference']);
        if (isset($data['card_brand'])) $payment->setCardBrand($data['card_brand']);
        if (isset($data['pan_suffix'])) $payment->setPanSuffix($data['pan_suffix']);
        if (isset($data['card_entry_method'])) $payment->setCardEntryMethod($data['card_entry_method']);
        if (isset($data['raw_json'])) $payment->setRawJson($data['raw_json']);
        if (isset($data['status'])) $payment->setStatus($data['status']);
        if (isset($data['match_confidence'])) $payment->setMatchConfidence((float)$data['match_confidence']);
        if (isset($data['fa_trans_type'])) $payment->setFaTransType((int)$data['fa_trans_type']);
        if (isset($data['fa_trans_no'])) $payment->setFaTransNo((int)$data['fa_trans_no']);
        if (isset($data['fa_bank_account'])) $payment->setFaBankAccount($data['fa_bank_account']);
        if (isset($data['error_log'])) $payment->setErrorLog($data['error_log']);
        if (isset($data['source_updated_at'])) $payment->setSourceUpdatedAt(new \DateTimeImmutable($data['source_updated_at']));
        return $payment;
    }
}
