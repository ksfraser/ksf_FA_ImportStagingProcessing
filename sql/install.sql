-- ksf_FA_ImportStagingProcessing Database Tables
-- Unified staging tables for post-import operations
-- Supports: WooCommerce, Square API, Square CSV, PayPal, Bank Import

-- ============================================================================
-- staging_customers - Unified customer staging from all sources
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_customers (
    id INT(11) NOT NULL AUTO_INCREMENT,
    source VARCHAR(32) NOT NULL COMMENT 'Source module: woocommerce, square_api, square_csv, paypal, bank',
    source_customer_id VARCHAR(64) DEFAULT NULL COMMENT 'Customer ID in source system',
    name VARCHAR(255) DEFAULT NULL,
    email VARCHAR(128) DEFAULT NULL,
    phone VARCHAR(32) DEFAULT NULL,
    address_line1 VARCHAR(128) DEFAULT NULL,
    address_line2 VARCHAR(128) DEFAULT NULL,
    city VARCHAR(64) DEFAULT NULL,
    province VARCHAR(64) DEFAULT NULL,
    postal_code VARCHAR(16) DEFAULT NULL,
    country VARCHAR(64) DEFAULT NULL,
    raw_json LONGTEXT DEFAULT NULL COMMENT 'Full source data as JSON',
    status VARCHAR(16) NOT NULL DEFAULT 'staged' COMMENT 'staged, validated, matched, processed, failed',
    fa_debtor_no INT(11) DEFAULT NULL COMMENT 'Matched FA debtor number',
    error_log TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_source (source),
    KEY idx_source_customer (source, source_customer_id),
    KEY idx_status (status),
    KEY idx_email (email),
    KEY idx_fa_debtor (fa_debtor_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- staging_transactions - Unified transaction staging from all sources
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_transactions (
    id INT(11) NOT NULL AUTO_INCREMENT,
    source VARCHAR(32) NOT NULL COMMENT 'Source module: woocommerce, square_api, square_csv, paypal, bank',
    source_transaction_id VARCHAR(64) DEFAULT NULL COMMENT 'Transaction ID in source system',
    source_order_id VARCHAR(64) DEFAULT NULL COMMENT 'Order ID in source system',
    source_payment_id VARCHAR(64) DEFAULT NULL COMMENT 'Payment ID in source system',
    transaction_date DATE DEFAULT NULL,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    tip_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    shipping_amount DECIMAL(15,2) DEFAULT 0.00,
    currency VARCHAR(8) DEFAULT 'CAD',
    customer_name VARCHAR(255) DEFAULT NULL,
    customer_email VARCHAR(128) DEFAULT NULL,
    customer_id VARCHAR(64) DEFAULT NULL COMMENT 'Customer ID in source system',
    raw_json LONGTEXT DEFAULT NULL COMMENT 'Full source data as JSON',
    status VARCHAR(16) NOT NULL DEFAULT 'staged' COMMENT 'staged, validated, matched, processed, failed',
    match_confidence DECIMAL(5,4) DEFAULT NULL COMMENT 'Match confidence score 0.0000-1.0000',
    fa_invoice_no INT(11) DEFAULT NULL COMMENT 'Created FA invoice number',
    fa_debtor_no INT(11) DEFAULT NULL COMMENT 'Matched FA debtor number',
    error_log TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_source (source),
    KEY idx_source_transaction (source, source_transaction_id),
    KEY idx_status (status),
    KEY idx_date (transaction_date),
    KEY idx_match_confidence (match_confidence),
    KEY idx_fa_invoice (fa_invoice_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- staging_line_items - Line items per staged transaction
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_line_items (
    id INT(11) NOT NULL AUTO_INCREMENT,
    staging_transaction_id INT(11) NOT NULL,
    sku VARCHAR(64) DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    quantity DECIMAL(15,2) DEFAULT 0.00,
    unit_price DECIMAL(15,2) DEFAULT 0.00,
    total_amount DECIMAL(15,2) DEFAULT 0.00,
    tax_amount DECIMAL(15,2) DEFAULT 0.00,
    discount_amount DECIMAL(15,2) DEFAULT 0.00,
    raw_json LONGTEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staging_transaction (staging_transaction_id),
    KEY idx_sku (sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- staging_mapping - Field mapping configuration per source
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_mapping (
    id INT(11) NOT NULL AUTO_INCREMENT,
    source VARCHAR(32) NOT NULL COMMENT 'Source module',
    source_field VARCHAR(128) NOT NULL COMMENT 'Field name in source data',
    target_field VARCHAR(128) NOT NULL COMMENT 'Target field in unified schema',
    transform VARCHAR(32) DEFAULT 'none' COMMENT 'Transformation: none, normalize, map, concat',
    default_value VARCHAR(255) DEFAULT NULL,
    is_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_source (source),
    UNIQUE KEY idx_source_mapping (source, source_field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- staging_log - Processing audit trail
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_log (
    id INT(11) NOT NULL AUTO_INCREMENT,
    record_type VARCHAR(32) NOT NULL COMMENT 'customer, transaction, line_item, payment',
    record_id INT(11) NOT NULL COMMENT 'ID in the respective staging table',
    action VARCHAR(32) NOT NULL COMMENT 'staged, validated, matched, processed, failed, reconciled',
    source VARCHAR(32) DEFAULT NULL,
    details TEXT DEFAULT NULL COMMENT 'JSON details of the action',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_record (record_type, record_id),
    KEY idx_action (action),
    KEY idx_source (source),
    KEY idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- staging_payments - Unified payment staging from all sources
-- Tracks individual payment tenders with fee/net calculation for reconciliation
-- Supports: credit_card, cash, gift_card, check, other
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_payments (
    id INT(11) NOT NULL AUTO_INCREMENT,
    source VARCHAR(32) NOT NULL COMMENT 'Source module: woocommerce, square_api, square_csv, paypal, bank',
    source_payment_id VARCHAR(64) DEFAULT NULL COMMENT 'Payment ID in source system',
    source_transaction_id VARCHAR(64) DEFAULT NULL COMMENT 'Linked transaction ID in source system',
    staging_transaction_id INT(11) DEFAULT NULL COMMENT 'FK to staging_transactions.id',
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    currency VARCHAR(8) DEFAULT 'CAD',
    fee DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Processing fee charged by provider',
    net_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'amount - fee = deposit amount',
    payment_method VARCHAR(32) DEFAULT NULL COMMENT 'credit_card, cash, gift_card, check, other',
    payment_date DATE DEFAULT NULL,
    reference VARCHAR(128) DEFAULT NULL COMMENT 'Check number, transaction reference, etc.',
    card_brand VARCHAR(32) DEFAULT NULL COMMENT 'Visa, Mastercard, Amex, Discover',
    pan_suffix VARCHAR(4) DEFAULT NULL COMMENT 'Last 4 digits of card',
    card_entry_method VARCHAR(16) DEFAULT NULL COMMENT 'swiped, dipped, keyed, online',
    raw_json LONGTEXT DEFAULT NULL COMMENT 'Full source data as JSON',
    status VARCHAR(16) NOT NULL DEFAULT 'staged' COMMENT 'staged, validated, matched, reconciled, failed',
    match_confidence DECIMAL(5,4) DEFAULT NULL COMMENT 'Match confidence score 0.0000-1.0000',
    fa_trans_type INT(11) DEFAULT NULL COMMENT 'FA trans type: 0=debtor_payment, 1=bank_deposit',
    fa_trans_no INT(11) DEFAULT NULL COMMENT 'Created/Matched FA transaction number',
    fa_bank_account VARCHAR(32) DEFAULT NULL COMMENT 'FA bank account code',
    error_log TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_source (source),
    KEY idx_source_payment (source, source_payment_id),
    KEY idx_status (status),
    KEY idx_payment_date (payment_date),
    KEY idx_payment_method (payment_method),
    KEY idx_staging_transaction (staging_transaction_id),
    KEY idx_match_confidence (match_confidence),
    KEY idx_fa_reference (fa_trans_type, fa_trans_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- staging_payment_matches - Payment reconciliation match audit trail
-- Tracks each match attempt: staged payment → FA bank/debtor transaction
-- ============================================================================
CREATE TABLE IF NOT EXISTS TB_PREFstaging_payment_matches (
    id INT(11) NOT NULL AUTO_INCREMENT,
    staging_payment_id INT(11) NOT NULL COMMENT 'FK to staging_payments.id',
    match_type VARCHAR(16) NOT NULL COMMENT 'exact, fuzzy, manual, none',
    match_confidence DECIMAL(5,4) DEFAULT NULL,
    fa_trans_type INT(11) DEFAULT NULL COMMENT 'FA trans type matched',
    fa_trans_no INT(11) DEFAULT NULL COMMENT 'FA trans number matched',
    fa_bank_account VARCHAR(32) DEFAULT NULL COMMENT 'FA bank account matched',
    match_status VARCHAR(16) NOT NULL DEFAULT 'matched' COMMENT 'matched, needs_review, rejected',
    matched_by VARCHAR(32) DEFAULT 'system' COMMENT 'system or user identifier',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_staging_payment (staging_payment_id),
    KEY idx_match_status (match_status),
    KEY idx_fa_reference (fa_trans_type, fa_trans_no)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================================
-- Upgrade notes for backward compatibility
-- ============================================================================
-- These existing tables coexist with the new unified staging:
--   0_ksf_import_square_transactions  (from FA_ImportSquareUp / ksf_FA_Square)
--   0_ksf_import_square_items         (from FA_ImportSquareUp / ksf_FA_Square)
--   0_square_staging_transactions     (from earlier staging)
--   0_square_staging_items            (from earlier staging)
--   etc.
--
-- Migration strategy:
-- ALTER TABLE ADD COLUMN IF NOT EXISTS approach:
--   SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
--   WHERE TABLE_NAME = '...' AND COLUMN_NAME = '...'
-- If column doesn't exist: ALTER TABLE ADD COLUMN ...
-- Never DROP or TRUNCATE existing prod tables.
