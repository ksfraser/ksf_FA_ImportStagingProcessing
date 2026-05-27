<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Models;

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

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getSource(): string { return $this->source; }

    public function getSourcePaymentId(): ?string { return $this->sourcePaymentId; }
    public function setSourcePaymentId(?string $id): void { $this->sourcePaymentId = $id; }

    public function getSourceTransactionId(): ?string { return $this->sourceTransactionId; }
    public function setSourceTransactionId(?string $id): void { $this->sourceTransactionId = $id; }

    public function getStagingTransactionId(): ?int { return $this->stagingTransactionId; }
    public function setStagingTransactionId(?int $id): void { $this->stagingTransactionId = $id; }

    public function getAmount(): float { return $this->amount; }
    public function setAmount(float $amount): void { $this->amount = $amount; }

    public function getCurrency(): string { return $this->currency; }
    public function setCurrency(string $currency): void { $this->currency = $currency; }

    public function getFee(): float { return $this->fee; }
    public function setFee(float $fee): void { $this->fee = $fee; }

    public function getNetAmount(): float { return $this->netAmount; }
    public function setNetAmount(float $net): void { $this->netAmount = $net; }

    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function setPaymentMethod(?string $method): void { $this->paymentMethod = $method; }

    public function getPaymentDate(): ?\DateTimeInterface { return $this->paymentDate; }
    public function setPaymentDate(?\DateTimeInterface $date): void { $this->paymentDate = $date; }

    public function getReference(): ?string { return $this->reference; }
    public function setReference(?string $ref): void { $this->reference = $ref; }

    public function getCardBrand(): ?string { return $this->cardBrand; }
    public function setCardBrand(?string $brand): void { $this->cardBrand = $brand; }

    public function getPanSuffix(): ?string { return $this->panSuffix; }
    public function setPanSuffix(?string $suffix): void { $this->panSuffix = $suffix; }

    public function getCardEntryMethod(): ?string { return $this->cardEntryMethod; }
    public function setCardEntryMethod(?string $method): void { $this->cardEntryMethod = $method; }

    public function getRawJson(): ?string { return $this->rawJson; }
    public function setRawJson(?string $json): void { $this->rawJson = $json; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    public function setMatchConfidence(?float $confidence): void { $this->matchConfidence = $confidence; }

    public function getFaTransType(): ?int { return $this->faTransType; }
    public function setFaTransType(?int $type): void { $this->faTransType = $type; }

    public function getFaTransNo(): ?int { return $this->faTransNo; }
    public function setFaTransNo(?int $no): void { $this->faTransNo = $no; }

    public function getFaBankAccount(): ?string { return $this->faBankAccount; }
    public function setFaBankAccount(?string $acct): void { $this->faBankAccount = $acct; }

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
