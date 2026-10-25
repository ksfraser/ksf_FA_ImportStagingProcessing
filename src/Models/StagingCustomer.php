<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Models;

/**
 * Data model for a unified staged customer from any import source.
 *
 * Carries source, source_customer_id, name, contact, address, and FA
 * debtor mapping fields. Status lifecycle: staged → validated → matched
 * → processed → failed.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-001-001 staging_customers table
 * @BABOK Related: FR-ISU-001-002 source column
 * @BABOK Related: FR-ISU-001-003 status tracking
 * @BABOK Related: FR-ISU-005-004 idempotent update path
 * @BABOK Related: NFR-ISU-003 TB_PREF convention
 */
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
    private ?\DateTimeInterface $sourceUpdatedAt;
    private ?\DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt;

    /**
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-002 source field
     */
    public function __construct(string $source)
    {
        $this->source = $source;
        $this->id = null;
        $this->status = 'staged';
        $this->sourceCustomerId = null;
        $this->name = null;
        $this->email = null;
        $this->phone = null;
        $this->addressLine1 = null;
        $this->addressLine2 = null;
        $this->city = null;
        $this->province = null;
        $this->postalCode = null;
        $this->country = null;
        $this->rawJson = null;
        $this->faDebtorNo = null;
        $this->errorLog = null;
        $this->sourceUpdatedAt = null;
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 id field
     */
    public function getId(): ?int { return $this->id; }
    /**
     * @param int $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 id field
     */
    public function setId(int $id): void { $this->id = $id; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-002 source field
     */
    public function getSource(): string { return $this->source; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 source_customer_id field
     */
    public function getSourceCustomerId(): ?string { return $this->sourceCustomerId; }
    /**
     * @param ?string $id
     * @since 2.0.0
     * @BABOK Related: FR-ISU-005-004 source_customer_id field
     */
    public function setSourceCustomerId(?string $id): void { $this->sourceCustomerId = $id; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 name field
     */
    public function getName(): ?string { return $this->name; }
    /**
     * @param ?string $name
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 name field
     */
    public function setName(?string $name): void { $this->name = $name; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 email field
     */
    public function getEmail(): ?string { return $this->email; }
    /**
     * @param ?string $email
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 email field
     */
    public function setEmail(?string $email): void { $this->email = $email; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 phone field
     */
    public function getPhone(): ?string { return $this->phone; }
    /**
     * @param ?string $phone
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 phone field
     */
    public function setPhone(?string $phone): void { $this->phone = $phone; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 address_line1 field
     */
    public function getAddressLine1(): ?string { return $this->addressLine1; }
    /**
     * @param ?string $address
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 address_line1 field
     */
    public function setAddressLine1(?string $address): void { $this->addressLine1 = $address; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 address_line2 field
     */
    public function getAddressLine2(): ?string { return $this->addressLine2; }
    /**
     * @param ?string $address
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 address_line2 field
     */
    public function setAddressLine2(?string $address): void { $this->addressLine2 = $address; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 city field
     */
    public function getCity(): ?string { return $this->city; }
    /**
     * @param ?string $city
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 city field
     */
    public function setCity(?string $city): void { $this->city = $city; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 province field
     */
    public function getProvince(): ?string { return $this->province; }
    /**
     * @param ?string $province
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 province field
     */
    public function setProvince(?string $province): void { $this->province = $province; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 postal_code field
     */
    public function getPostalCode(): ?string { return $this->postalCode; }
    /**
     * @param ?string $code
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 postal_code field
     */
    public function setPostalCode(?string $code): void { $this->postalCode = $code; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 country field
     */
    public function getCountry(): ?string { return $this->country; }
    /**
     * @param ?string $country
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 country field
     */
    public function setCountry(?string $country): void { $this->country = $country; }

    /**
     * @return ?string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 raw_json field
     */
    public function getRawJson(): ?string { return $this->rawJson; }
    /**
     * @param ?string $json
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 raw_json field
     */
    public function setRawJson(?string $json): void { $this->rawJson = $json; }

    /**
     * @return string
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-003 status field
     */
    public function getStatus(): string { return $this->status; }
    /**
     * @param string $status
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-003 status field
     */
    public function setStatus(string $status): void { $this->status = $status; }

    /**
     * @return ?int
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-002 fa_debtor_no field
     */
    public function getFaDebtorNo(): ?int { return $this->faDebtorNo; }
    /**
     * @param ?int $no
     * @since 2.0.0
     * @BABOK Related: FR-ISU-012-002 fa_debtor_no field
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
     * @BABOK Related: FR-ISU-001-002 source_updated_at field
     */
    public function getSourceUpdatedAt(): ?\DateTimeInterface { return $this->sourceUpdatedAt; }
    /**
     * @param ?\DateTimeInterface $dt
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-002 source_updated_at field
     */
    public function setSourceUpdatedAt(?\DateTimeInterface $dt): void { $this->sourceUpdatedAt = $dt; }

    /**
     * Serialize the customer to an associative array for DAO persistence.
     *
     * @return array<string, mixed>
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 full record serialization
     */
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
            'source_updated_at' => $this->sourceUpdatedAt ? $this->sourceUpdatedAt->format('Y-m-d H:i:s') : null,
        ];
    }

    /**
     * Hydrate a customer from an associative array (DAO result set).
     *
     * @param array $data Row data from the database
     * @return self
     * @since 2.0.0
     * @BABOK Related: FR-ISU-001-001 full record hydration
     */
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
        if (isset($data['source_updated_at'])) $customer->setSourceUpdatedAt(new \DateTimeImmutable($data['source_updated_at']));
        return $customer;
    }
}
