# BABOK Business Analysis — ksf_FA_ImportStagingProcessing

> **Module Code**: ISU (Import Staging Processing)
> **Version**: 2.4.4
> **Status**: Active
> **Numbering Scheme**: BR-* (Business Requirement), FR-* (Functional Requirement),
> UC-* (Use Case), UT-* (Unit Test), UAT-* (User Acceptance Test).
> All IDs are module-prefixed with `ISU-` to avoid cross-module collisions.

---

## 1. Business Analysis Overview

### 1.1 Purpose

This document captures the business analysis for the ksf_FA_ImportStagingProcessing
module, aligned with BABOK (Business Analysis Body of Knowledge) practices. It
maps business needs to requirements and solution design, and provides the
numbering scheme used across Requirements.md, RTM.md, source code `@BABOK`
tags, and test cases.

### 1.2 Scope

The module provides a unified staging layer and processing pipeline for
post-import operations from third-party sources (WooCommerce, Square API,
Square CSV, PayPal, Bank Import) into FrontAccounting. It owns the shared
staging tables (`staging_customers`, `staging_transactions`,
`staging_payments`, `staging_mapping`, `staging_log`) and the consistent
Stage → Validate → Match → Process → Audit pipeline.

Source modules own their import mechanics; this module owns staging and
processing only. FA entity creation (debtors, invoices, payments) is
delegated to ksf_FA_Customer and ksf_FA_Payment via capability-based hooks
(`CREATE_CUSTOMER`, `CREATE_PAYMENT`) — ISU never writes FA core tables
directly.

---

## 2. Stakeholders

| Stakeholder | BABOK Role | Interest |
|-------------|------------|----------|
| FA Administrator | Domain SME | UI reviews, processing workflow testing, activation |
| Finance / Accounting | Business Sponsor | Accurate transaction records, reconciliation reports |
| Source Module Devs (Square/Woo/PayPal/Bank) | Implementation | Interface design reviews, hook API validation |
| Developer (ksf) | Implementation | AGENTS.md conventions, peer reviews, @BABOK traceability |
| QA / Tester | Validation | Test coverage, use case execution, UAT sign-off |

---

## 3. Business Needs

### 3.1 Current State (As-Is)

- Each import module has its own staging logic, tables, and processing code
- Duplicate code across WooCommerce, Square API, Square CSV, PayPal, Bank Import
- Inconsistent field handling and matching behavior
- No unified matching or reconciliation across sources
- No standardized hooks API for inter-module staging access

### 3.2 Desired State (To-Be)

- Single unified staging schema shared across all import modules
- Consistent pipeline: Stage → Validate → Match → Process → Audit
- Scoring-based transaction matching (95% auto-approve, 80% review threshold)
- Event-driven architecture for staging lifecycle events
- Standard 4-method hooks API (`getModuleConstants`, `getModuleCapabilities`,
  `hasCapability`, `respondToCapabilityRequest`)
- Config delegation: source modules own GL/bank/payment config via `SourceConfig`
- Backward-compatible ALTER TABLE schema upgrades (zero data loss)
- 100% requirements↔code traceability via @BABOK tags + RTM
- 100% test coverage (unit + integration + user)

### 3.3 Gap Analysis

| Gap | Current | Target | Solution | Related FR |
|-----|---------|--------|----------|------------|
| Staging duplication | Per-module tables | Unified schema | `staging_*` tables + DAO layer | FR-ISU-001 |
| Processing inconsistency | Ad-hoc pipelines | Stage→Validate→Match→Process→Audit | `StagingService`, `ProcessingPipeline` | FR-ISU-002 |
| Inter-module API | None | 4-method hooks contract | `hooks.php` responders | FR-ISU-003 |
| Config coupling | ISU owns GL/bank | Source modules own config | `SourceConfig` value object | FR-ISU-010 |
| Traceability | Ad-hoc docs | @BABOK tags + RTM | PHPDoc tags on every function | NFR-ISU-007 |
| Test coverage | Partial | 100% unit + integration + user | PHPUnit + curl/chromium tests | NFR-ISU-008 |

---

## 4. Business Requirements (BR)

### BR-ISU-001: Unified Staging Management

**Statement**: The system SHALL provide unified staging tables for customers,
transactions, payments, line items, mapping config, and audit log, shared
across all import sources.

**Rationale**: Eliminates duplicate staging logic and enables cross-source
matching and reconciliation.

**Acceptance Criteria**:
- `staging_customers` table exists with `source` column distinguishing origin
- `staging_transactions` table exists with normalized common fields
- `staging_payments` table exists for payment staging
- `staging_mapping` table stores field mapping configuration per source
- `staging_log` table records processing audit trail
- Per-record status tracking (staged, validated, matched, processed, failed)

**Related FRs**: FR-ISU-001 through FR-ISU-007

---

### BR-ISU-002: Processing Pipeline

**Statement**: The system SHALL implement a consistent Stage → Validate → Match
→ Process → Audit pipeline with scoring-based matching and configurable
thresholds.

**Rationale**: Standardizes behavior across sources and enables auto-approval
of high-confidence matches.

**Acceptance Criteria**:
- Validate staged records against schema rules
- Match staged transactions against existing FA transactions (scoring-based)
- Auto-approve matches with >= 95% confidence
- Flag matches between 80-95% for manual review
- Process approved matches into FA entities
- Log all processing steps in `staging_log`

**Related FRs**: FR-ISU-008 through FR-ISU-014

---

### BR-ISU-003: Inter-Module Communication (Hooks API)

**Statement**: The system SHALL expose a standard hooks-based API for
inter-module staging access, implementing the 4-method discovery contract.

**Rationale**: Source modules (Square, WooCommerce, etc.) must be able to
stage data without knowing ISU's internal implementation.

**Acceptance Criteria**:
- `getModuleConstants()` returns module name and capabilities
- `getModuleCapabilities()` returns capability definitions
- `hasCapability()` reports whether a capability is supported
- `respondToCapabilityRequest()` dispatches `staging:*`, `matching:*`,
  `reconciliation:*`, `processing:*`, `mapping:*`, `audit:*` requests
- `STAGE_ENTITY` / `STAGING_EXISTS` DTO-based responders available

**Related FRs**: FR-ISU-015 through FR-ISU-020

---

### BR-ISU-004: Config Delegation

**Statement**: The system SHALL delegate source-specific configuration
(GL accounts, bank accounts, payment methods) to importing modules via the
`SourceConfig` value object, retaining only source-agnostic settings.

**Rationale**: ISU is a generic staging backend. Owning Square-specific GL
accounts violates SRP and creates coupling.

**Acceptance Criteria**:
- `SourceConfig` value object carries source-specific GL/bank/payment values
- ISU config table contains only source-agnostic fields
- Processing methods accept `SourceConfig` from calling module
- ISU falls back to generic defaults when source config not provided

**Related FRs**: FR-ISU-010, FR-ISU-011

---

### BR-ISU-005: Duplicate Detection & Idempotency

**Statement**: The system SHALL prevent re-import of already-staged records
via source + source_id duplicate detection.

**Rationale**: Re-importing the same CSV or API data must not create duplicate
staging rows.

**Acceptance Criteria**:
- `transaction_id` / `source_payment_id` existence checked before insert
- Duplicate records skipped with notification
- Idempotent inserts (update-if-exists path available)

**Related FRs**: FR-ISU-005, FR-ISU-006, FR-ISU-007

---

### BR-ISU-006: Backward-Compatible Schema Upgrades

**Statement**: The system SHALL support backward-compatible ALTER TABLE
schema upgrades for production data with zero data loss.

**Rationale**: Existing `0_ksf_import_square_*` tables with matched data must
be preserved during migration.

**Acceptance Criteria**:
- Install schema uses hardcoded `0_` company prefix
- Upgrades use ALTER TABLE only, never DROP/recreate
- `check_table()` gate prevents redundant imports

**Related FRs**: FR-ISU-016

---

### BR-ISU-007: Traceability & Test Coverage

**Statement**: The system SHALL maintain 100% requirements↔code traceability
via `@BABOK` tags on all functions (rolled up to classes and files) and an
RTM, with 100% test coverage.

**Rationale**: Enables audit of requirement implementation and prevents
undocumented code.

**Acceptance Criteria**:
- Every function carries `@BABOK Related: <ID>` in its docblock
- Class and file docblocks roll up applicable requirement IDs
- RTM traces every FR → UC → Code File → Test
- 100% PHPUnit code coverage (unit + integration)
- Integration + user tests via curl/chromium

**Related FRs**: NFR-ISU-007, NFR-ISU-008

---

## 5. Non-Functional Requirements (NFR)

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

## 6. Use Cases (UC)

| UC ID | Name | Trigger | Primary Actor | Related FRs |
|-------|------|---------|---------------|-------------|
| UC-ISU-001 | Stage Customer | Source module provides customer data | Source Module | FR-ISU-001, FR-ISU-005 |
| UC-ISU-002 | Stage Transaction | Source module provides transaction data | Source Module | FR-ISU-002, FR-ISU-005 |
| UC-ISU-003 | Stage Payment | Source module provides payment data | Source Module | FR-ISU-003, FR-ISU-005 |
| UC-ISU-004 | Stage Line Item | Source module provides line item data | Source Module | FR-ISU-004 |
| UC-ISU-005 | Validate Staged Record | Record inserted into staging | System (automatic) | FR-ISU-008 |
| UC-ISU-006 | Match Transactions | Processing pipeline runs | FA Administrator / System | FR-ISU-009, FR-ISU-010 |
| UC-ISU-007 | Auto-approve Match | Confidence >= 95% | System (automatic) | FR-ISU-010 |
| UC-ISU-008 | Flag for Review | Confidence 80-95% | System (automatic) | FR-ISU-011 |
| UC-ISU-009 | Process Approved Match | Match approved | FA Administrator / System | FR-ISU-012 |
| UC-ISU-010 | Reconcile Payment | Payment staged | FA Administrator / System | FR-ISU-013, FR-ISU-014 |
| UC-ISU-011 | Configure Mapping | Administrator sets up field mapping | FA Administrator | FR-ISU-015 |
| UC-ISU-012 | Audit Trail Review | Any staging/processing operation | FA Administrator | FR-ISU-016 |
| UC-ISU-013 | Schema Upgrade | Module installation/update | System (automatic) | FR-ISU-017 |
| UC-ISU-014 | Capability Discovery | External module queries ISU | Source Module | FR-ISU-018, FR-ISU-019, FR-ISU-020 |

---

## 7. Functional Requirements (FR)

### FR-ISU-001: staging_customers table

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-001-001 | System shall provide unified `staging_customers` table | Must |
| FR-ISU-001-002 | `source` column distinguishes origin (woocommerce, square_api, square_csv, paypal, bank) | Must |
| FR-ISU-001-003 | Per-record status tracking column | Must |

### FR-ISU-002: staging_transactions table

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-002-001 | System shall provide unified `staging_transactions` table | Must |
| FR-ISU-002-002 | Normalized common fields (total_amount, currency, source_transaction_id, etc.) | Must |
| FR-ISU-002-003 | `source` column distinguishes origin | Must |

### FR-ISU-003: staging_payments table

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-003-001 | System shall provide unified `staging_payments` table | Must |
| FR-ISU-003-002 | Tenders, fees, net amounts, card details stored | Must |
| FR-ISU-003-003 | `net_amount = amount - fee` auto-calculated if not provided | Must |

### FR-ISU-004: staging_line_items table

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-004-001 | System shall provide line items linked to parent transaction | Must |
| FR-ISU-004-002 | `staging_transaction_id` foreign key link | Must |

### FR-ISU-005: Duplicate detection

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-005-001 | Check `source_transaction_id` existence before insert | Must |
| FR-ISU-005-002 | Check `source_payment_id` existence before insert | Must |
| FR-ISU-005-003 | Skip duplicates with notification | Must |
| FR-ISU-005-004 | Idempotent update-if-exists path | Should |

### FR-ISU-006: staging_mapping table

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-006-001 | System shall provide `staging_mapping` config table | Must |
| FR-ISU-006-002 | Field mapping configuration per source | Must |

### FR-ISU-007: staging_log table

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-007-001 | System shall provide `staging_log` audit table | Must |
| FR-ISU-007-002 | Status, timestamps, errors recorded | Must |

### FR-ISU-008: Validation

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-008-001 | Validate staged records against schema rules | Must |
| FR-ISU-008-002 | ValidationService rejects invalid records | Must |

### FR-ISU-009: Matching

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-009-001 | Match staged transactions against existing FA transactions | Must |
| FR-ISU-009-002 | Scoring-based matching algorithm | Must |

### FR-ISU-010: Auto-approve threshold

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-010-001 | Auto-approve matches with >= 95% confidence | Must |
| FR-ISU-010-002 | Threshold configurable | Should |

### FR-ISU-011: Review threshold

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-011-001 | Flag matches between 80-95% for manual review | Must |
| FR-ISU-011-002 | Threshold configurable | Should |

### FR-ISU-012: Process approved matches

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-012-001 | Process approved matches into FA entities | Must |
| FR-ISU-012-002 | Delegate debtor creation to ksf_FA_Customer via CREATE_CUSTOMER | Must |

### FR-ISU-013: Reconcile payments

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-013-001 | Reconcile staged payments against FA bank/debtor transactions | Must |
| FR-ISU-013-002 | Scoring-based payment match (amount 40%, date 25%, reference 20%, method 15%) | Must |

### FR-ISU-014: Auto-reconcile threshold

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-014-001 | Auto-reconcile payments with >= 95% match confidence | Must |
| FR-ISU-014-002 | Flag 80-95% for manual review | Must |

### FR-ISU-015: Field mapping configuration

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-015-001 | Store field mapping configuration per source | Must |
| FR-ISU-015-002 | UI for staging management (optional) | Should |

### FR-ISU-016: Audit logging

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-016-001 | Log all processing steps in `staging_log` | Must |
| FR-ISU-016-002 | Log all events for audit purposes | Must |

### FR-ISU-017: Backward-compatible schema

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-017-001 | Install schema uses hardcoded `0_` company prefix | Must |
| FR-ISU-017-002 | Upgrades use ALTER TABLE only, never DROP/recreate | Must |
| FR-ISU-017-003 | `check_table()` gate prevents redundant imports | Must |

### FR-ISU-018: getModuleConstants

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-018-001 | Implement `getModuleConstants()` hook method | Must |
| FR-ISU-018-002 | Return module name and capabilities | Must |

### FR-ISU-019: getModuleCapabilities

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-019-001 | Implement `getModuleCapabilities()` hook method | Must |
| FR-ISU-019-002 | Return capability definitions with methods and events | Must |

### FR-ISU-020: hasCapability + respondToCapabilityRequest

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-020-001 | Implement `hasCapability()` hook method | Must |
| FR-ISU-020-002 | Implement `respondToCapabilityRequest()` hook method | Must |
| FR-ISU-020-003 | Dispatch `staging:*`, `matching:*`, `reconciliation:*`, `processing:*` requests | Must |

### FR-ISU-021: SourceConfig value object

| ID | Requirement | Priority |
|----|-------------|----------|
| FR-ISU-021-001 | Provide `SourceConfig` value object for source-specific config | Must |
| FR-ISU-021-002 | Carry GL/bank/payment values per source | Must |
| FR-ISU-021-003 | Immutable fluent setters (`with*`) | Should |

---

## 8. Solution Approach

### 8.1 Architecture

Layered architecture following AGENTS_ARCH conventions:

```
ksf_FA_ImportStagingProcessing/
├── src/
│   ├── Config/           — SourceConfig value object
│   ├── Contracts/        — Interfaces (StagingManagerInterface, ProcessorInterface, etc.)
│   ├── DAO/              — Data access layer (Table Gateway pattern)
│   ├── Exceptions/       — Custom exception hierarchy
│   ├── Models/           — StagingCustomer, StagingTransaction, etc.
│   ├── Services/         — StagingService, ProcessingPipeline, MatchingService, DtoAdapter
│   └── Validators/       — TransactionValidator, CustomerValidator, PaymentValidator
├── hooks.php             — FA hooks integration (4-method contract + STAGE_* responders)
├── ksf_import_staging.php — Module entry point
├── pages/                — FA UI pages
├── sql/                  — Database schemas (0_ prefix)
└── tests/                — PHPUnit suite (Unit + Integration)
```

### 8.2 Data Flow

```
Source Module (Square/Woo/PayPal/Bank)
  ↓ hook_invoke STAGE_* / respondToCapabilityRequest staging:*
ISU Staging Tables (staging_customers, staging_transactions, staging_payments)
  ↓ Validation (Validators)
  ↓ Matching (MatchingService — scoring)
  ↓ Processing (ProcessingPipeline → CREATE_CUSTOMER / CREATE_PAYMENT via hooks)
FA Ledger (debtors, invoices, payments)
  ↓ Audit Trail (staging_log)
```

### 8.3 External Dependencies

- **ksf_FA_Common** — shared platform contracts (DateConverter, ContactResolver)
- **ksf_FA_Customer** — CREATE_CUSTOMER responder (debtor creation)
- **ksf_FA_Payment** — CREATE_PAYMENT responder (payment creation)
- **ksfraser/staging-dto** — DTO package for cross-module staging
- **ksfraser/ksf-modules-dao** — DAO base class
- **ksfraser/traits** — reusable traits
- **ksfraser/exceptions** — exception hierarchy
- **ksfraser/file** — format-aware file IO
- **ksfraser/famock** — FA mock routines for testing

---

## 9. Assumptions and Constraints

### Assumptions
- Source CSV/API formats are consistent and stable
- FA 2.4.x is the target platform
- MySQL/MariaDB is the database backend
- PHP 7.3+ available on server

### Constraints
- PHP 7.3 compatibility (no PHP 8+ features)
- FA's `db_query()` abstraction must be used for all database operations (PDO only in tests/standalone)
- Security sections registered via `install_access()`
- SQL tables use hardcoded `0_` company prefix
- `@TB_PREF@` / `{TB_PREF}` not used in SQL files

---

## 10. Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| Data loss during schema migration | Critical | ALTER TABLE only, never DROP/recreate |
| Breaking existing import flows | High | Full backward-compat testing |
| PHP version constraints | Medium | Target PHP 7.3+, avoid 8+ features |
| Date format mismatch across sources | High | DateConverterInterface with DI |
| Duplicate data on re-import | High | Source + source_id duplicate detection |
| Undocumented code (no @BABOK tags) | High | 100% tagging enforced via code review |

---

## 11. BABOK Techniques Applied

| Technique | BABOK Ref | Application |
|-----------|-----------|-------------|
| Business Process Modeling | 10.4 | Import → staging → processing workflow |
| Data Flow Diagrams | 10.14 | Source → Staging → FA data movement |
| Data Modeling | 10.15 | Unified staging tables |
| Document Analysis | 10.18 | Source module code review |
| Functional Decomposition | 10.20 | Requirements → Use Cases → Code → Tests |
| Interface Analysis | 10.25 | Module hook communication (4-method contract) |
| Metrics and KPIs | 10.28 | Test coverage, match accuracy |
| Process Modeling | 10.35 | Stage, Validate, Match, Process flows |
| Risk Analysis | 10.39 | Data loss, compat, PHP version |
| Scope Modeling | 10.42 | In-scope / out-of-scope matrix |
| Stakeholder List | 10.44 | FA Admin, Developers, Finance, QA |
| Traceability Matrix | 10.47 | RTM.md |
| UML Diagrams | 10.48 | UML.md |

---

## 12. Requirements Traceability Summary

| Requirement Type | ID Range | Count |
|------------------|----------|-------|
| Business Requirements (BR) | BR-ISU-001 to BR-ISU-007 | 7 |
| Functional Requirements (FR) | FR-ISU-001-001 to FR-ISU-021-003 | ~50 |
| Use Cases (UC) | UC-ISU-001 to UC-ISU-014 | 14 |
| Non-Functional (NFR) | NFR-ISU-001 to NFR-ISU-009 | 9 |

See **RTM.md** for the full Requirements Traceability Matrix mapping each
requirement to use case, code file(s), and test case(s).
