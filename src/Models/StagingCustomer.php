<?php
declare(strict_types=1);

namespace Ksfraser\ImportStaging\Models;

class StagingCustomer
{
    private ?int $id;
    private string $source;
    private ?string $sourceCustomerId;
    private ?string $name;
    private ?string $email;
    private ?string $phone;
    private ?string $addressLine1;
    private ?string $addressLine2;
    private ?string $city;
    private ?string $province;
    private ?string $postalCode;
    private ?string $country;
    private ?string $rawJson;
    private string $status;
    private ?int $faDebtorNo;
    private ?string $errorLog;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    public function __construct(string $source)
    {
        $this->source = $source;
        $this->status = 'staged';
    }

    public function getId(): ?int { return $this->id; }
    public function setId(int $id): void { $this->id = $id; }

    public function getSource(): string { return $this->source; }

    public function getSourceCustomerId(): ?string { return $this->sourceCustomerId; }
    public function setSourceCustomerId(?string $id): void { $this->sourceCustomerId = $id; }

    public function getName(): ?string { return $this->name; }
    public function setName(?string $name): void { $this->name = $name; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): void { $this->email = $email; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): void { $this->phone = $phone; }

    public function getAddressLine1(): ?string { return $this->addressLine1; }
    public function setAddressLine1(?string $address): void { $this->addressLine1 = $address; }

    public function getAddressLine2(): ?string { return $this->addressLine2; }
    public function setAddressLine2(?string $address): void { $this->addressLine2 = $address; }

    public function getCity(): ?string { return $this->city; }
    public function setCity(?string $city): void { $this->city = $city; }

    public function getProvince(): ?string { return $this->province; }
    public function setProvince(?string $province): void { $this->province = $province; }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $code): void { $this->postalCode = $code; }

    public function getCountry(): ?string { return $this->country; }
    public function setCountry(?string $country): void { $this->country = $country; }

    public function getRawJson(): ?string { return $this->rawJson; }
    public function setRawJson(?string $json): void { $this->rawJson = $json; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): void { $this->status = $status; }

    public function getFaDebtorNo(): ?int { return $this->faDebtorNo; }
    public function setFaDebtorNo(?int $no): void { $this->faDebtorNo = $no; }

    public function getErrorLog(): ?string { return $this->errorLog; }
    public function setErrorLog(?string $log): void { $this->errorLog = $log; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(?\DateTimeInterface $dt): void { $this->createdAt = $dt; }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeInterface $dt): void { $this->updatedAt = $dt; }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'source' => $this->source,
            'source_customer_id' => $this->sourceCustomerId,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_line1' => $this->addressLine1,
            'address_line2' => $this->addressLine2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'raw_json' => $this->rawJson,
            'status' => $this->status,
            'fa_debtor_no' => $this->faDebtorNo,
            'error_log' => $this->errorLog,
        ];
    }

    public static function fromArray(array $data): self
    {
        $customer = new self($data['source'] ?? 'unknown');
        if (isset($data['id'])) $customer->setId((int)$data['id']);
        if (isset($data['source_customer_id'])) $customer->setSourceCustomerId($data['source_customer_id']);
        if (isset($data['name'])) $customer->setName($data['name']);
        if (isset($data['email'])) $customer->setEmail($data['email']);
        if (isset($data['phone'])) $customer->setPhone($data['phone']);
        if (isset($data['address_line1'])) $customer->setAddressLine1($data['address_line1']);
        if (isset($data['address_line2'])) $customer->setAddressLine2($data['address_line2']);
        if (isset($data['city'])) $customer->setCity($data['city']);
        if (isset($data['province'])) $customer->setProvince($data['province']);
        if (isset($data['postal_code'])) $customer->setPostalCode($data['postal_code']);
        if (isset($data['country'])) $customer->setCountry($data['country']);
        if (isset($data['raw_json'])) $customer->setRawJson($data['raw_json']);
        if (isset($data['status'])) $customer->setStatus($data['status']);
        if (isset($data['fa_debtor_no'])) $customer->setFaDebtorNo((int)$data['fa_debtor_no']);
        if (isset($data['error_log'])) $customer->setErrorLog($data['error_log']);
        return $customer;
    }
}
