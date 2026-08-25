# Requirements Specification — ksf_FA_ImportStagingProcessing

## 1. Business Context

### 1.1 Problem Statement
FrontAccounting (FA) imports data from multiple third-party sources (WooCommerce, Square API, Square CSV, PayPal, Bank Import), each with its own staging logic, matching pipeline, and processing code. This creates duplication, inconsistent behavior, and maintenance burden. A unified staging layer and processing pipeline is needed to standardize the flow from import → staging → matching → FA entity creation across all sources.

### 1.2 Business Objectives
| ID | Objective | Priority | Status |
|----|-----------|----------|--------|
| BO-01 | Eliminate duplicate staging logic across import modules | High | 🔄 Planned |
| BO-02 | Provide unified staging tables for all source types | High | 🔄 Planned |
| BO-03 | Implement consistent matching/reconciliation pipeline | High | 🔄 Planned |
| BO-04 | Enable source-agnostic processing of staged data | High | 🔄 Planned |
| BO-05 | Preserve backward compatibility with existing prod data | Critical | 🔄 Planned |
| BO-06 | Provide hooks-based API for inter-module staging access | Medium | 🔄 Planned |
| BO-07 | Ensure 100% test coverage across all classes | High | 🔄 Planned |
| BO-08 | Support event-driven architecture for staging lifecycle | Medium | 🔄 Planned |

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

---

## 2. Scope

### 2.1 In Scope
- Unified staging tables for customers and transactions from all sources
- Consistent processing pipeline: Stage → Validate → Match → Process → Audit
- Source-agnostic field mapping configuration
- Scoring-based transaction matching (95% auto-approve, 80% review threshold)
- Event-driven architecture for staging lifecycle events
- Backward-compatible schema upgrades (ALTER TABLE strategy)
- Standard 4-method hooks API for inter-module communication
- Composer package for direct DAO/DTO/Repository access
- Full test suite (Unit + Integration + UAT)
- Project documentation (BABOK, RTM, UML, Use Cases, Test Plan)

### 2.2 Out of Scope
- WooCommerce-specific import mechanics (handled by ksf_generate)
- Square API-specific import mechanics (handled by ksf_FA_Square)
- Square CSV-specific parsing (handled by FA_ImportSquareUp)
- PayPal-specific import mechanics (handled by FA_ImportSquareUp)
- Bank import mechanics (handled by ksf_bank_import)
- FA entity creation logic beyond matching/reconciliation
- Real-time sync or webhook handling

---

## 3. Functional Requirements

### FR-01: Unified Staging Management
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-01.01 | System shall provide unified `staging_customers` table for all sources | `source` column distinguishes origin |
| FR-01.02 | System shall provide unified `staging_transactions` table for all sources | Normalized common fields |
| FR-01.03 | System shall provide `staging_mapping` table for field mapping config | Source-specific field maps |
| FR-01.04 | System shall provide `staging_log` table for processing audit trail | Status, timestamps, errors |
| FR-01.05 | System shall accept records from WooCommerce, Square API, Square CSV, PayPal, Bank Import | Via hooks API or DAO |
| FR-01.06 | System shall track per-record status (staged, validated, matched, processed, failed) | Status column |
| FR-01.07 | System shall support backward-compatible ALTER TABLE upgrades for prod data | Zero data loss |

### FR-02: Processing Pipeline
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-02.01 | System shall validate staged records against schema rules | ValidationService |
| FR-02.02 | System shall match staged transactions against existing FA transactions | Scoring-based matching |
| FR-02.03 | System shall auto-approve matches with >= 95% confidence | Configurable threshold |
| FR-02.04 | System shall flag matches between 80-95% for manual review | Configurable threshold |
| FR-02.05 | System shall process approved matches into FA entities | ProcessingPipeline |
| FR-02.06 | System shall log all processing steps in staging_log | Audit trail |

### FR-03: Inter-Module Communication
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-03.01 | System shall implement the 4 standard hook methods | getModuleConstants, getModuleCapabilities, hasCapability, respondToCapabilityRequest |
| FR-03.02 | System shall expose `staging` capability for other modules | Insert/query staged data |
| FR-03.03 | System shall provide DAO classes for direct programmatic access | Via composer package |
| FR-03.04 | System shall support both hooks API and direct DAO access | Optional injection |

### FR-04: Event-Driven Architecture
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-04.01 | System shall emit events on staging lifecycle: staged, validated, matched, processed, reconciled | EventEmitterTrait |
| FR-04.02 | System shall allow other modules to subscribe to staging events | Observer pattern |
| FR-04.03 | System shall log all events for audit purposes | staging_log |

### FR-06: Payment Staging & Reconciliation
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-06.01 | System shall provide unified `staging_payments` table for all sources | Tenders, fees, net amounts, card details |
| FR-06.02 | System shall provide `staging_payment_matches` table for reconciliation audit trail | Each match attempt logged |
| FR-06.03 | System shall stage individual payment tenders with full metadata | card_brand, pan_suffix, entry_method, fees |
| FR-06.04 | System shall calculate net_amount = amount - fee for each payment | Auto-calculated if not provided |
| FR-06.05 | System shall link payments to parent staging_transactions via FK | staging_transaction_id |
| FR-06.06 | System shall score payment matches against FA bank/debtor transactions | amount 40%, date 25%, reference 20%, method 15% |
| FR-06.07 | System shall auto-reconcile payments with >= 95% match confidence | Configurable threshold |
| FR-06.08 | System shall flag payment matches between 80-95% for manual review | Configurable threshold |
| FR-06.09 | System shall provide reconciliation queue processing | Batch reconcile staged payments |
| FR-06.10 | System shall track payment reconciliation status lifecycles | staged, validated, matched, reconciled, failed |

### FR-05: Configuration & Administration
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-05.01 | System shall store field mapping configuration per source | staging_mapping table |
| FR-05.02 | System shall provide UI for staging management (optional, via pages/) | |
| FR-05.03 | System shall provide error resolution interface | |
| FR-05.04 | System shall log errors with context for debugging | |

### FR-07: Source-Agnostic Date Handling
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-07.01 | System shall use DateConverterInterface for all date format conversions | SRP — date logic in one injectable service |
| FR-07.02 | Source modules shall inject DateConverterInterface, never call FA date globals directly | DI pattern, testability |
| FR-07.03 | DateConverterInterface shall convert between user display format and ISO Y-m-d | User may configure MM/DD/YYYY, DD/MM/YYYY, or YYYY-MM-DD |
| FR-07.04 | DateConverterInterface shall be provided by ksf_FA_Common platform module | Shared contract, all consuming modules depend on it |
| FR-07.05 | FADateConverter shall wrap FA's date2sql()/sql2date()/Today() behind the interface | FA-specific implementation |
| FR-07.06 | StaticDateConverter shall provide a testable implementation without FA dependencies | Unit testing, CLI tools |
| FR-07.07 | Source modules shall depend on ksf_FA_Common for DateConverterInterface | Composer path repository dependency |

### FR-08: Shared Platform Dependencies
| ID | Requirement | Notes |
|----|-------------|-------|
| FR-08.01 | All KSF modules shall depend on ksf_FA_Common for shared contracts | DateConverter, ContactType, etc. |
| FR-08.02 | ksf_FA_Common shall be activated before all other modules | Platform foundation |
| FR-08.03 | Module composer.json shall declare path repository dependency on ksf_FA_Common | `"type": "path", "url": "../ksf_FA_Common"` |

### FR-09: Hooks+DTO Interface (ISU ↔ External Modules)

**Description**: ISU provides hooks and DTOs (in `ksfraser/staging-dto` package)
for external modules to stage data. External modules create DTOs and call ISU
hooks. ISU handles all DB operations and FA entity creation. ISU responds about
staging data ONLY — never about FA journal entries, customers, or entities.

| ID | Requirement | Notes |
|----|-------------|-------|
| FR-09.01 | ISU shall expose `stageEntity` hook accepting any `StagingEntity` subclass | Single hook for all entity types |
| FR-09.02 | ISU shall expose `stagingExists` hook accepting `StagingExistsQuery` | Returns `StagingExistsResult` |
| FR-09.03 | ISU shall expose `processQueue` hook accepting source string | Triggers processing for a source |
| FR-09.04 | ISU shall check `$dto->getVersion()` for version handling | Each DTO defines own version |
| FR-09.05 | ISU shall check `$dto instanceof` for type-specific processing | StagingOrder vs StagingInvoice, etc. |
| FR-09.06 | ISU shall respond about staging data ONLY | Never expose FA entities to external modules |
| FR-09.07 | ISU shall handle duplicate detection (source + sourceId) | Idempotent inserts |
| FR-09.08 | External modules shall depend on `ksfraser/staging-dto` | Shared DTO package |
| FR-09.09 | External modules shall call ISU hooks, not do DB operations | ISU owns all DB work |
| FR-09.10 | Bank import shall coordinate with ISU for cash flow matching | Check stagingExists before inserting |

**Legacy Note:** The repository adapter pattern (FR-09.01-09.05 in previous version)
was deprecated in v2.5.0. See `src/Staging/*RepositoryAdapter.php` for deprecated
adapter classes.

---

## 4. Non-Functional Requirements

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-01 | PHP Compatibility | >=7.3 |
| NFR-02 | FrontAccounting Version | 2.4.x |
| NFR-03 | Database | MySQL/MariaDB with TB_PREF convention |
| NFR-04 | Security | Input validation, SQL injection prevention, XSS protection, CSRF |
| NFR-05 | Performance | Efficient queries with proper indexing, pagination for large datasets |
| NFR-06 | Error Handling | Custom exception hierarchy with factory methods |
| NFR-07 | Test Coverage | 100% for all classes and methods |
| NFR-08 | SOLID/DRY/DI | Layered architecture with dependency injection |
| NFR-09 | Design Patterns | Strategy, Factory, Repository, Observer, DTO |

---

## 5. Data Model

### 5.1 Unified Staging Tables
```
staging_customers          - Unified customer staging from all sources
staging_transactions       - Unified transaction staging from all sources
staging_mapping            - Field mapping configuration per source
staging_log                - Processing audit trail
```

### 5.2 Backward Compatibility
Existing prod tables (`0_ksf_import_square_transactions`, `0_square_staging_transactions`, etc.) will be preserved and migrated via ALTER TABLE strategy when needed.

---

## 6. Architecture Plan

```
ksf_FA_ImportStagingProcessing/
├── src/
│   ├── Contracts/           — Interfaces (StagingManagerInterface, ProcessorInterface, etc.)
│   ├── Services/            — Staging services, processing pipeline
│   ├── Models/              — StagingCustomer, StagingTransaction, etc.
│   ├── Exceptions/          — Custom exception hierarchy
│   └── Validators/          — Input validation
├── pages/                   — FA UI pages (optional)
├── sql/                     — Database schemas
├── tests/                   — PHPUnit tests
├── hooks.php                — FA hooks integration
├── ksf_import_staging.php   — Module entry point
└── composer.json
```
