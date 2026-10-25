# Requirements Specification — ksf_FA_ImportStagingProcessing

> **Module Code**: ISU (Import Staging Processing)
> **Version**: 2.4.4
> **FA Version**: 2.4.x
> **PHP**: >=7.3
> **Numbering Scheme**: BR-* (Business Requirement), FR-* (Functional Requirement),
> UC-* (Use Case), UT-* (Unit Test), UAT-* (User Acceptance Test). All IDs
> module-prefixed with `ISU-`.
> **Traceability**: Every FR carries `@BABOK Related: <ID>` in source code;
> RTM.md traces FR → UC → Code File → Test.

---

## 1. Business Context

### 1.1 Problem Statement

FrontAccounting (FA) imports data from multiple third-party sources
(WooCommerce, Square API, Square CSV, PayPal, Bank Import), each with its own
staging logic, matching pipeline, and processing code. This creates
duplication, inconsistent behavior, and maintenance burden. A unified staging
layer and processing pipeline is needed to standardize the flow from import →
staging → matching → FA entity creation across all sources.

### 1.2 Business Objectives

| ID | Objective | Priority | Status |
|----|-----------|----------|--------|
| BO-01 | Eliminate duplicate staging logic across import modules | High | Implemented |
| BO-02 | Provide unified staging tables for all source types | High | Implemented |
| BO-03 | Implement consistent matching/reconciliation pipeline | High | Implemented |
| BO-04 | Enable source-agnostic processing of staged data | High | Implemented |
| BO-05 | Preserve backward compatibility with existing prod data | Critical | Implemented |
| BO-06 | Provide hooks-based API for inter-module staging access | Medium | Implemented |
| BO-07 | Ensure 100% test coverage across all classes | High | Implemented |
| BO-08 | Support event-driven architecture for staging lifecycle | Medium | Implemented |

### 1.3 Stakeholders

| Role | Interest | Engagement |
|------|----------|------------|
| FA Administrator | Configures imports, monitors staging, reviews matches | Active |
| Finance / Accounting | Relies on accurate transaction records | Consulted |
| IT / Developer | Maintains and enhances module | Responsible |
| WooCommerce Module | Pushes staged orders/customers into unified tables | Active |
| Square API Module | Pushes staged transactions/customers into unified tables | Active |
| Square CSV Module | Pushes CSV transactions into unified tables | Active |
| PayPal Module | Pushes PayPal transactions into unified tables | Active |
| Bank Import Module | Pushes bank transactions for reconciliation | Active |
| QA / Tester | Validates requirements via unit/integration/user tests | Active |

---

## 2. Scope

### 2.1 In Scope

- Unified staging tables for customers, transactions, payments, line items,
  mapping config, and audit log from all sources
- Consistent processing pipeline: Stage → Validate → Match → Process → Audit
- Source-agnostic field mapping configuration
- Scoring-based transaction matching (95% auto-approve, 80% review threshold)
- Event-driven architecture for staging lifecycle events
- Backward-compatible schema upgrades (ALTER TABLE strategy)
- Standard 4-method hooks API for inter-module communication
- `SourceConfig` value object for config delegation (source modules own GL/bank)
- Full test suite (Unit + Integration + User via curl/chromium)
- Project documentation (BABOK, RTM, Requirements, Use Cases, Test Plan)
- `@BABOK` traceability tags on all functions, rolled up to classes and files

### 2.2 Out of Scope

- WooCommerce-specific import mechanics (handled by ksf_generate / ksf_FA_Woocommerce)
- Square API-specific import mechanics (handled by ksf_FA_Square)
- Square CSV-specific parsing (handled by FA_ImportSquareUp)
- PayPal-specific import mechanics (handled by FA_ImportSquareUp)
- Bank import mechanics (handled by ksf_bank_import)
- FA entity creation logic beyond matching/reconciliation (delegated to ksf_FA_Customer / ksf_FA_Payment)
- Real-time sync or webhook handling

---

## 3. Functional Requirements

### FR-ISU-001: staging_customers table

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-001-001 | System shall provide unified `staging_customers` table | Must | `src/DAO/StagingCustomerDAO.php`, `sql/install.sql` | UT-ISU-001-001 |
| FR-ISU-001-002 | `source` column distinguishes origin | Must | `src/Models/StagingCustomer.php` | UT-ISU-001-002 |
| FR-ISU-001-003 | Per-record status tracking column | Must | `src/Models/StagingCustomer.php` | UT-ISU-001-003 |

### FR-ISU-002: staging_transactions table

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-002-001 | System shall provide unified `staging_transactions` table | Must | `src/DAO/StagingTransactionDAO.php`, `sql/install.sql` | UT-ISU-002-001 |
| FR-ISU-002-002 | Normalized common fields | Must | `src/Models/StagingTransaction.php` | UT-ISU-002-002 |
| FR-ISU-002-003 | `source` column distinguishes origin | Must | `src/Models/StagingTransaction.php` | UT-ISU-002-003 |

### FR-ISU-003: staging_payments table

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-003-001 | System shall provide unified `staging_payments` table | Must | `src/DAO/StagingPaymentDAO.php`, `sql/install.sql` | UT-ISU-003-001 |
| FR-ISU-003-002 | Tenders, fees, net amounts, card details stored | Must | `src/Models/StagingPayment.php` | UT-ISU-003-002 |
| FR-ISU-003-003 | `net_amount = amount - fee` auto-calculated | Must | `src/Services/StagingService.php::stagePayment()` | UT-ISU-003-003 |

### FR-ISU-004: staging_line_items table

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-004-001 | Line items linked to parent transaction | Must | `src/DAO/StagingLineItemDAO.php`, `src/Models/StagingLineItem.php` | UT-ISU-004-001 |
| FR-ISU-004-002 | `staging_transaction_id` foreign key link | Must | `src/Models/StagingLineItem.php` | UT-ISU-004-002 |

### FR-ISU-005: Duplicate detection

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-005-001 | Check `source_transaction_id` existence before insert | Must | `src/Services/StagingService.php::stageTransaction()` | UT-ISU-005-001 |
| FR-ISU-005-002 | Check `source_payment_id` existence before insert | Must | `src/Services/StagingService.php::stagePayment()` | UT-ISU-005-002 |
| FR-ISU-005-003 | Skip duplicates with notification | Must | `src/Services/StagingService.php` | UT-ISU-005-003 |
| FR-ISU-005-004 | Idempotent update-if-exists path | Should | `src/Services/StagingService.php::stageOrUpdate*()` | UT-ISU-005-004 |

### FR-ISU-006: staging_mapping table

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-006-001 | System shall provide `staging_mapping` config table | Must | `src/DAO/StagingMappingDAO.php`, `sql/install.sql` | UT-ISU-006-001 |
| FR-ISU-006-002 | Field mapping configuration per source | Must | `src/DAO/StagingMappingDAO.php` | UT-ISU-006-002 |

### FR-ISU-007: staging_log table

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-007-001 | System shall provide `staging_log` audit table | Must | `src/DAO/StagingLogDAO.php`, `sql/install.sql` | UT-ISU-007-001 |
| FR-ISU-007-002 | Status, timestamps, errors recorded | Must | `src/DAO/StagingLogDAO.php::log()` | UT-ISU-007-002 |

### FR-ISU-008: Validation

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-008-001 | Validate staged records against schema rules | Must | `src/Validators/TransactionValidator.php`, `CustomerValidator.php`, `PaymentValidator.php` | UT-ISU-008-001 |
| FR-ISU-008-002 | ValidationService rejects invalid records | Must | `src/Services/StagingService.php` | UT-ISU-008-002 |

### FR-ISU-009: Matching

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-009-001 | Match staged transactions against existing FA transactions | Must | `src/Services/MatchingService.php` | UT-ISU-009-001 |
| FR-ISU-009-002 | Scoring-based matching algorithm | Must | `src/Services/MatchingService.php`, `src/Services/Matching/MatchConfig.php`, `Similarity.php` | UT-ISU-009-002 |

### FR-ISU-010: Auto-approve threshold (>= 95%)

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-010-001 | Auto-approve matches with >= 95% confidence | Must | `src/Services/MatchingService.php::autoApprove()` | UT-ISU-010-001 |
| FR-ISU-010-002 | Threshold configurable | Should | `src/Services/Matching/MatchConfig.php` | UT-ISU-010-002 |

### FR-ISU-011: Review threshold (80-95%)

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-011-001 | Flag matches between 80-95% for manual review | Must | `src/Services/MatchingService.php::needsReview()` | UT-ISU-011-001 |
| FR-ISU-011-002 | Threshold configurable | Should | `src/Services/Matching/MatchConfig.php` | UT-ISU-011-002 |

### FR-ISU-012: Process approved matches

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-012-001 | Process approved matches into FA entities | Must | `src/Services/ProcessingPipeline.php`, `src/Services/StagingService.php::createDebtorFromStaged()` | UT-ISU-012-001 |
| FR-ISU-012-002 | Delegate debtor creation to ksf_FA_Customer via CREATE_CUSTOMER | Must | `src/Services/StagingService.php::createDebtorFromStaged()` | IT-ISU-012-002 |

### FR-ISU-013: Reconcile payments

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-013-001 | Reconcile staged payments against FA bank/debtor transactions | Must | `src/Services/StagingService.php::reconcilePayment()` | UT-ISU-013-001 |
| FR-ISU-013-002 | Scoring-based payment match (amount 40%, date 25%, reference 20%, method 15%) | Must | `src/Services/MatchingService.php::calculatePaymentMatchScore()` | UT-ISU-013-002 |

### FR-ISU-014: Auto-reconcile threshold (>= 95%)

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-014-001 | Auto-reconcile payments with >= 95% match confidence | Must | `src/Services/StagingService.php::reconcilePayment()` | UT-ISU-014-001 |
| FR-ISU-014-002 | Flag 80-95% for manual review | Must | `src/Services/StagingService.php::reconcilePayment()` | UT-ISU-014-002 |

### FR-ISU-015: Field mapping configuration

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-015-001 | Store field mapping configuration per source | Must | `src/DAO/StagingMappingDAO.php` | UT-ISU-015-001 |
| FR-ISU-015-002 | UI for staging management (optional) | Should | `pages/admin.php` | UT-ISU-015-002 |

### FR-ISU-016: Audit logging

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-016-001 | Log all processing steps in `staging_log` | Must | `src/DAO/StagingLogDAO.php::log()` | UT-ISU-016-001 |
| FR-ISU-016-002 | Log all events for audit purposes | Must | `src/Services/StagingService.php` | UT-ISU-016-002 |

### FR-ISU-017: Backward-compatible schema

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-017-001 | Install schema uses hardcoded `0_` company prefix | Must | `sql/install.sql` | UT-ISU-017-001 |
| FR-ISU-017-002 | Upgrades use ALTER TABLE only, never DROP/recreate | Must | `sql/install.sql` | UT-ISU-017-002 |
| FR-ISU-017-003 | `check_table()` gate prevents redundant imports | Must | `hooks.php::activate_extension()` | UT-ISU-017-003 |

### FR-ISU-018: getModuleConstants

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-018-001 | Implement `getModuleConstants()` hook method | Must | `hooks.php::getModuleConstants()` | UT-ISU-018-001 |
| FR-ISU-018-002 | Return module name and capabilities | Must | `hooks.php::getModuleConstants()` | UT-ISU-018-002 |

### FR-ISU-019: getModuleCapabilities

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-019-001 | Implement `getModuleCapabilities()` hook method | Must | `hooks.php::getModuleCapabilities()` | UT-ISU-019-001 |
| FR-ISU-019-002 | Return capability definitions with methods and events | Must | `hooks.php::getModuleCapabilities()` | UT-ISU-019-002 |

### FR-ISU-020: hasCapability + respondToCapabilityRequest

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-020-001 | Implement `hasCapability()` hook method | Must | `hooks.php::hasCapability()` | UT-ISU-020-001 |
| FR-ISU-020-002 | Implement `respondToCapabilityRequest()` hook method | Must | `hooks.php::respondToCapabilityRequest()` | UT-ISU-020-002 |
| FR-ISU-020-003 | Dispatch `staging:*`, `matching:*`, `reconciliation:*`, `processing:*` requests | Must | `hooks.php::respondToCapabilityRequest()` | UT-ISU-020-003 |

### FR-ISU-021: SourceConfig value object

| ID | Requirement | Priority | Code File(s) | Test |
|----|-------------|----------|--------------|------|
| FR-ISU-021-001 | Provide `SourceConfig` value object for source-specific config | Must | `src/Config/SourceConfig.php` | UT-ISU-021-001 |
| FR-ISU-021-002 | Carry GL/bank/payment values per source | Must | `src/Config/SourceConfig.php` | UT-ISU-021-002 |
| FR-ISU-021-003 | Immutable fluent setters (`with*`) | Should | `src/Config/SourceConfig.php` | UT-ISU-021-003 |

---

## 4. Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-ISU-001 | PHP Compatibility | >=7.3 |
| NFR-ISU-002 | FrontAccounting Version | 2.4.x |
| NFR-ISU-003 | Database | MySQL/MariaDB with TB_PREF convention |
| NFR-ISU-004 | Security | Input validation, SQL injection prevention, XSS protection, CSRF |
| NFR-ISU-005 | Performance | Efficient queries with proper indexing, pagination |
| NFR-ISU-006 | Error Handling | Custom exception hierarchy with factory methods |
| NFR-ISU-007 | Traceability | @BABOK tags on all functions; RTM complete |
| NFR-ISU-008 | Test Coverage | 100% unit + integration + user tests |
| NFR-ISU-009 | Design Patterns | Strategy, Factory, Repository, Observer, DTO |

---

## 5. Data Model

### 5.1 Unified Staging Tables

| Table | Description |
|-------|-------------|
| `staging_customers` | Unified customer staging from all sources |
| `staging_transactions` | Unified transaction staging from all sources |
| `staging_payments` | Unified payment staging from all sources |
| `staging_line_items` | Line items linked to parent transaction |
| `staging_mapping` | Field mapping configuration per source |
| `staging_log` | Processing audit trail |

All tables use hardcoded `0_` company prefix (PHP code uses `TB_PREF` constant).

### 5.2 Backward Compatibility

Existing prod tables (`0_ksf_import_square_transactions`, etc.) will be
preserved and migrated via ALTER TABLE strategy when needed. No DROP/recreate.
