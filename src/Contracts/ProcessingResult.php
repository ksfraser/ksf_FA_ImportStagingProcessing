<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Contracts;

class ProcessingResult
{
    private bool $success;
    private ?int $recordId;
    private string $action;
    private ?int $faReferenceNo;
    private array $errors;
    private ?float $matchConfidence;
    private ?\DateTimeInterface $processedAt;
    private array $metadata;

    public function __construct(
        bool $success,
        ?int $recordId = null,
        string $action = '',
        ?int $faReferenceNo = null,
        array $errors = [],
        ?float $matchConfidence = null,
        ?\DateTimeInterface $processedAt = null,
        array $metadata = []
    ) {
        $this->success = $success;
        $this->recordId = $recordId;
        $this->action = $action;
        $this->faReferenceNo = $faReferenceNo;
        $this->errors = $errors;
        $this->matchConfidence = $matchConfidence;
        $this->processedAt = $processedAt ?? new \DateTimeImmutable();
        $this->metadata = $metadata;
    }

    public function isSuccess(): bool { return $this->success; }
    public function getRecordId(): ?int { return $this->recordId; }
    public function getAction(): string { return $this->action; }
    public function getFaReferenceNo(): ?int { return $this->faReferenceNo; }
    public function getErrors(): array { return $this->errors; }
    public function getMatchConfidence(): ?float { return $this->matchConfidence; }
    public function getProcessedAt(): \DateTimeInterface { return $this->processedAt; }
    public function getMetadata(): array { return $this->metadata; }

    public static function success(int $recordId, string $action, ?int $faRef = null, float $confidence = null): self
    {
        return new self(true, $recordId, $action, $faRef, [], $confidence);
    }

    public static function failure(int $recordId, string $action, array $errors): self
    {
        return new self(false, $recordId, $action, null, $errors);
    }
}
