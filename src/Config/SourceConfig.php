<?php
declare(strict_types=1);

namespace ksfraser\FrontAccounting\ImportStaging\Config;

/**
 * Type-safe value object for source-specific configuration.
 *
 * Importing modules (Square, WooCommerce, etc.) pass an instance of this
 * class to ISU processing methods, replacing the old pattern of ISU owning
 * source-specific config fields (GL accounts, bank accounts, etc.).
 *
 * ISU remains generic: it reads GL/bank/payment values from this object
 * at processing time rather than from its own config table.
 *
 * @since 2.0.0
 * @BABOK Related: FR-ISU-010 Config Delegation
 */
class SourceConfig
{
    /** @var string Source identifier (e.g., 'square', 'woocommerce') */
    private $source;

    /** @var int GL account for primary/card transactions */
    private $glAccount;

    /** @var int GL account for cash transactions */
    private $cashGl;

    /** @var int Transfer destination GL account */
    private $xferToGl;

    /** @var int Bank account for primary deposits */
    private $bankAccount;

    /** @var int Transfer destination bank account */
    private $xferToBank;

    /** @var int Cash bank account */
    private $cashBank;

    /** @var int Default payment method for card transactions */
    private $defaultPayCard;

    /** @var int Default payment method for cash transactions */
    private $defaultPayCash;

    /** @var bool Use card PAN suffix as customer branch */
    private $useCardAsBranch;

    /** @var bool Allow SKU editing on staged items */
    private $allowSkuChange;

    /**
     * @param string $source      Source identifier (e.g., 'square')
     * @param int    $glAccount   GL account for card transactions
     * @param int    $bankAccount Bank account for deposits
     */
    public function __construct(
        string $source = '',
        int $glAccount = 0,
        int $bankAccount = 0
    ) {
        $this->source = $source;
        $this->glAccount = $glAccount;
        $this->bankAccount = $bankAccount;

        // Defaults for optional fields
        $this->cashGl = 0;
        $this->xferToGl = 0;
        $this->xferToBank = 0;
        $this->cashBank = 0;
        $this->defaultPayCard = 0;
        $this->defaultPayCash = 0;
        $this->useCardAsBranch = false;
        $this->allowSkuChange = false;
    }

    /**
     * Create from an associative array (e.g., from Settings::toArray()).
     *
     * @param array $data Key-value pairs
     * @return self
     * @BABOK Related: FR-ISU-010-003
     */
    public static function fromArray(array $data): self
    {
        $config = new self(
            $data['source'] ?? '',
            (int)($data['gl_account'] ?? 0),
            (int)($data['bank_account'] ?? 0)
        );

        $config->cashGl = (int)($data['cash_gl'] ?? 0);
        $config->xferToGl = (int)($data['xfer_to_gl'] ?? 0);
        $config->xferToBank = (int)($data['xfer_to_bank'] ?? 0);
        $config->cashBank = (int)($data['cash_bank'] ?? 0);
        $config->defaultPayCard = (int)($data['default_pay_card'] ?? 0);
        $config->defaultPayCash = (int)($data['default_pay_cash'] ?? 0);
        $config->useCardAsBranch = (bool)($data['useCardAsBranch'] ?? false);
        $config->allowSkuChange = (bool)($data['allowSkuChange'] ?? false);

        return $config;
    }

    /**
     * Export to associative array.
     *
     * @return array<string, mixed>
     * @BABOK Related: FR-ISU-010-003
     */
    public function toArray(): array
    {
        return [
            'source'            => $this->source,
            'gl_account'        => $this->glAccount,
            'cash_gl'           => $this->cashGl,
            'xfer_to_gl'        => $this->xferToGl,
            'bank_account'      => $this->bankAccount,
            'xfer_to_bank'      => $this->xferToBank,
            'cash_bank'         => $this->cashBank,
            'default_pay_card'  => $this->defaultPayCard,
            'default_pay_cash'  => $this->defaultPayCash,
            'useCardAsBranch'   => $this->useCardAsBranch,
            'allowSkuChange'    => $this->allowSkuChange,
        ];
    }

    // -- Getters ----------------------------------------------------------

    /** @BABOK Related: FR-ISU-010-001 */
    public function getSource(): string
    {
        return $this->source;
    }

    /** @BABOK Related: FR-ISU-011-002 */
    public function getGlAccount(): int
    {
        return $this->glAccount;
    }

    /** @BABOK Related: FR-ISU-011-002 */
    public function getCashGl(): int
    {
        return $this->cashGl;
    }

    /** @BABOK Related: FR-ISU-011-002 */
    public function getXferToGl(): int
    {
        return $this->xferToGl;
    }

    /** @BABOK Related: FR-ISU-011-003 */
    public function getBankAccount(): int
    {
        return $this->bankAccount;
    }

    /** @BABOK Related: FR-ISU-011-003 */
    public function getXferToBank(): int
    {
        return $this->xferToBank;
    }

    /** @BABOK Related: FR-ISU-011-003 */
    public function getCashBank(): int
    {
        return $this->cashBank;
    }

    /** @BABOK Related: FR-ISU-011-004 */
    public function getDefaultPayCard(): int
    {
        return $this->defaultPayCard;
    }

    /** @BABOK Related: FR-ISU-011-004 */
    public function getDefaultPayCash(): int
    {
        return $this->defaultPayCash;
    }

    /** @BABOK Related: FR-ISU-006-005 */
    public function isUseCardAsBranch(): bool
    {
        return $this->useCardAsBranch;
    }

    /** @BABOK Related: FR-ISU-005-007 */
    public function isAllowSkuChange(): bool
    {
        return $this->allowSkuChange;
    }

    // -- Fluent setters (immutable pattern) --------------------------------

    /** @BABOK Related: FR-ISU-010-003 */
    public function withGlAccount(int $gl): self
    {
        $clone = clone $this;
        $clone->glAccount = $gl;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withCashGl(int $gl): self
    {
        $clone = clone $this;
        $clone->cashGl = $gl;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withXferToGl(int $gl): self
    {
        $clone = clone $this;
        $clone->xferToGl = $gl;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withBankAccount(int $bank): self
    {
        $clone = clone $this;
        $clone->bankAccount = $bank;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withXferToBank(int $bank): self
    {
        $clone = clone $this;
        $clone->xferToBank = $bank;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withCashBank(int $bank): self
    {
        $clone = clone $this;
        $clone->cashBank = $bank;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withDefaultPayCard(int $payType): self
    {
        $clone = clone $this;
        $clone->defaultPayCard = $payType;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withDefaultPayCash(int $payType): self
    {
        $clone = clone $this;
        $clone->defaultPayCash = $payType;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withUseCardAsBranch(bool $flag): self
    {
        $clone = clone $this;
        $clone->useCardAsBranch = $flag;
        return $clone;
    }

    /** @BABOK Related: FR-ISU-010-003 */
    public function withAllowSkuChange(bool $flag): self
    {
        $clone = clone $this;
        $clone->allowSkuChange = $flag;
        return $clone;
    }

    /**
     * Determine the GL account to use for a given payment type.
     *
     * @param float $amount Transaction amount
     * @return int GL account ID
     * @BABOK Related: FR-ISU-011-002
     */
    public function getGlForAmount(float $amount): int
    {
        if ($amount > 0 && $this->glAccount > 0) {
            return $this->glAccount;
        }
        if ($amount <= 0 && $this->cashGl > 0) {
            return $this->cashGl;
        }
        return $this->glAccount;
    }
}
