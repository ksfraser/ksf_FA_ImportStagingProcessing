# Requirements Traceability Matrix (RTM) — ksf_FA_ImportStagingProcessing

> **Module Code**: ISU
> **Numbering Scheme**: BR-* (Business Requirement), FR-* (Functional Requirement),
> UC-* (Use Case), UT-* (Unit Test), IT-* (Integration Test), UAT-* (User Acceptance Test).
> All IDs module-prefixed with `ISU-`.
>
> **Legend**:
> - **Covered**: FR has corresponding code, test, and UC
> - **Partial**: FR has code but incomplete test coverage
> - **Planned**: FR is defined but not yet implemented
> - **N/A**: FR is not applicable to current release

---

## 1. Requirement → Use Case → Code → Test

| Req ID | Requirement Summary | Use Case | Code File(s) | Unit Test | Integration Test | Status |
|--------|--------------------|----------|--------------|-----------|------------------|--------|
| **FR-ISU-001-001** | Unified `staging_customers` table | UC-ISU-001 | `src/DAO/StagingCustomerDAO.php`, `sql/install.sql` | UT-ISU-001-001 | — | Covered |
| **FR-ISU-001-002** | `source` column distinguishes origin | UC-ISU-001 | `src/Models/StagingCustomer.php` | UT-ISU-001-002 | — | Covered |
| **FR-ISU-001-003** | Per-record status tracking | UC-ISU-001 | `src/Models/StagingCustomer.php` | UT-ISU-001-003 | — | Covered |
| **FR-ISU-002-001** | Unified `staging_transactions` table | UC-ISU-002 | `src/DAO/StagingTransactionDAO.php`, `sql/install.sql` | UT-ISU-002-001 | — | Covered |
| **FR-ISU-002-002** | Normalized common fields | UC-ISU-002 | `src/Models/StagingTransaction.php` | UT-ISU-002-002 | — | Covered |
| **FR-ISU-002-003** | `source` column distinguishes origin | UC-ISU-002 | `src/Models/StagingTransaction.php` | UT-ISU-002-003 | — | Covered |
| **FR-ISU-003-001** | Unified `staging_payments` table | UC-ISU-003 | `src/DAO/StagingPaymentDAO.php`, `sql/install.sql` | UT-ISU-003-001 | — | Covered |
| **FR-ISU-003-002** | Tenders, fees, net amounts, card details | UC-ISU-003 | `src/Models/StagingPayment.php` | UT-ISU-003-002 | — | Covered |
| **FR-ISU-003-003** | `net_amount = amount - fee` auto-calculated | UC-ISU-003 | `src/Services/StagingService.php::stagePayment()` | UT-ISU-003-003 | — | Covered |
| **FR-ISU-004-001** | Line items linked to parent transaction | UC-ISU-004 | `src/DAO/StagingLineItemDAO.php`, `src/Models/StagingLineItem.php` | UT-ISU-004-001 | — | Covered |
| **FR-ISU-004-002** | `staging_transaction_id` foreign key | UC-ISU-004 | `src/Models/StagingLineItem.php` | UT-ISU-004-002 | — | Covered |
| **FR-ISU-005-001** | Check `source_transaction_id` before insert | UC-ISU-002 | `src/Services/StagingService.php::stageTransaction()` | UT-ISU-005-001 | — | Covered |
| **FR-ISU-005-002** | Check `source_payment_id` before insert | UC-ISU-003 | `src/Services/StagingService.php::stagePayment()` | UT-ISU-005-002 | — | Covered |
| **FR-ISU-005-003** | Skip duplicates with notification | UC-ISU-002, UC-ISU-003 | `src/Services/StagingService.php` | UT-ISU-005-003 | — | Covered |
| **FR-ISU-005-004** | Idempotent update-if-exists path | UC-ISU-002, UC-ISU-003 | `src/Services/StagingService.php::stageOrUpdate*()` | UT-ISU-005-004 | — | Covered |
| **FR-ISU-006-001** | `staging_mapping` config table | UC-ISU-011 | `src/DAO/StagingMappingDAO.php`, `sql/install.sql` | UT-ISU-006-001 | — | Covered |
| **FR-ISU-006-002** | Field mapping per source | UC-ISU-011 | `src/DAO/StagingMappingDAO.php` | UT-ISU-006-002 | — | Covered |
| **FR-ISU-007-001** | `staging_log` audit table | UC-ISU-012 | `src/DAO/StagingLogDAO.php`, `sql/install.sql` | UT-ISU-007-001 | — | Covered |
| **FR-ISU-007-002** | Status, timestamps, errors recorded | UC-ISU-012 | `src/DAO/StagingLogDAO.php::log()` | UT-ISU-007-002 | — | Covered |
| **FR-ISU-008-001** | Validate staged records | UC-ISU-005 | `src/Validators/TransactionValidator.php`, `CustomerValidator.php`, `PaymentValidator.php` | UT-ISU-008-001 | — | Covered |
| **FR-ISU-008-002** | Validation rejects invalid records | UC-ISU-005 | `src/Services/StagingService.php` | UT-ISU-008-002 | — | Covered |
| **FR-ISU-009-001** | Match staged vs FA transactions | UC-ISU-006 | `src/Services/MatchingService.php` | UT-ISU-009-001 | — | Covered |
| **FR-ISU-009-002** | Scoring-based matching algorithm | UC-ISU-006 | `src/Services/MatchingService.php`, `MatchConfig.php`, `Similarity.php` | UT-ISU-009-002 | — | Covered |
| **FR-ISU-010-001** | Auto-approve >= 95% confidence | UC-ISU-007 | `src/Services/MatchingService.php::autoApprove()` | UT-ISU-010-001 | — | Covered |
| **FR-ISU-010-002** | Threshold configurable | UC-ISU-007 | `src/Services/Matching/MatchConfig.php` | UT-ISU-010-002 | — | Covered |
| **FR-ISU-011-001** | Flag 80-95% for review | UC-ISU-008 | `src/Services/MatchingService.php::needsReview()` | UT-ISU-011-001 | — | Covered |
| **FR-ISU-011-002** | Threshold configurable | UC-ISU-008 | `src/Services/Matching/MatchConfig.php` | UT-ISU-011-002 | — | Covered |
| **FR-ISU-012-001** | Process approved matches into FA | UC-ISU-009 | `src/Services/ProcessingPipeline.php`, `StagingService.php::createDebtorFromStaged()` | UT-ISU-012-001 | IT-ISU-012-002 | Covered |
| **FR-ISU-012-002** | Delegate debtor creation via CREATE_CUSTOMER | UC-ISU-009 | `src/Services/StagingService.php::createDebtorFromStaged()` | UT-ISU-012-002 | IT-ISU-012-002 | Covered |
| **FR-ISU-013-001** | Reconcile staged payments | UC-ISU-010 | `src/Services/StagingService.php::reconcilePayment()` | UT-ISU-013-001 | — | Covered |
| **FR-ISU-013-002** | Scoring-based payment match | UC-ISU-010 | `src/Services/MatchingService.php::calculatePaymentMatchScore()` | UT-ISU-013-002 | — | Covered |
| **FR-ISU-014-001** | Auto-reconcile >= 95% confidence | UC-ISU-010 | `src/Services/StagingService.php::reconcilePayment()` | UT-ISU-014-001 | — | Covered |
| **FR-ISU-014-002** | Flag 80-95% for review | UC-ISU-010 | `src/Services/StagingService.php::reconcilePayment()` | UT-ISU-014-002 | — | Covered |
| **FR-ISU-015-001** | Field mapping per source | UC-ISU-011 | `src/DAO/StagingMappingDAO.php` | UT-ISU-015-001 | — | Covered |
| **FR-ISU-015-002** | Staging management UI | UC-ISU-011 | `pages/admin.php` | UT-ISU-015-002 | — | Covered |
| **FR-ISU-016-001** | Log all processing steps | UC-ISU-012 | `src/DAO/StagingLogDAO.php::log()` | UT-ISU-016-001 | — | Covered |
| **FR-ISU-016-002** | Log all events | UC-ISU-012 | `src/Services/StagingService.php` | UT-ISU-016-002 | — | Covered |
| **FR-ISU-017-001** | Install schema uses `0_` prefix | UC-ISU-013 | `sql/install.sql` | UT-ISU-017-001 | — | Covered |
| **FR-ISU-017-002** | ALTER TABLE only, no DROP/recreate | UC-ISU-013 | `sql/install.sql` | UT-ISU-017-002 | — | Covered |
| **FR-ISU-017-003** | `check_table()` gate | UC-ISU-013 | `hooks.php::activate_extension()` | UT-ISU-017-003 | — | Covered |
| **FR-ISU-018-001** | `getModuleConstants()` implemented | UC-ISU-014 | `hooks.php::getModuleConstants()` | UT-ISU-018-001 | — | Covered |
| **FR-ISU-018-002** | Return module name + capabilities | UC-ISU-014 | `hooks.php::getModuleConstants()` | UT-ISU-018-002 | — | Covered |
| **FR-ISU-019-001** | `getModuleCapabilities()` implemented | UC-ISU-014 | `hooks.php::getModuleCapabilities()` | UT-ISU-019-001 | — | Covered |
| **FR-ISU-019-002** | Return capability definitions | UC-ISU-014 | `hooks.php::getModuleCapabilities()` | UT-ISU-019-002 | — | Covered |
| **FR-ISU-020-001** | `hasCapability()` implemented | UC-ISU-014 | `hooks.php::hasCapability()` | UT-ISU-020-001 | — | Covered |
| **FR-ISU-020-002** | `respondToCapabilityRequest()` implemented | UC-ISU-014 | `hooks.php::respondToCapabilityRequest()` | UT-ISU-020-002 | — | Covered |
| **FR-ISU-020-003** | Dispatch `staging:*`/`matching:*`/etc. | UC-ISU-014 | `hooks.php::respondToCapabilityRequest()` | UT-ISU-020-003 | — | Covered |
| **FR-ISU-021-001** | `SourceConfig` value object | UC-ISU-002 | `src/Config/SourceConfig.php` | UT-ISU-021-001 | — | Covered |
| **FR-ISU-021-002** | Carry GL/bank/payment values | UC-ISU-002 | `src/Config/SourceConfig.php` | UT-ISU-021-002 | — | Covered |
| **FR-ISU-021-003** | Immutable fluent setters (`with*`) | UC-ISU-002 | `src/Config/SourceConfig.php` | UT-ISU-021-003 | — | Covered |

---

## 2. Non-Functional Requirements → Code → Test

| Req ID | Requirement Summary | Code File(s) | Test | Status |
|--------|--------------------|--------------|------|--------|
| **NFR-ISU-001** | PHP >=7.3 compatibility | `composer.json` | CI platform check | Covered |
| **NFR-ISU-002** | FA 2.4.x integration | `hooks.php` | Manual activation test | Covered |
| **NFR-ISU-003** | TB_PREF convention | All SQL queries, `TB_PREF` constant | Code review | Covered |
| **NFR-ISU-004** | Security best practices | All code | Code review | Covered |
| **NFR-ISU-005** | Performance optimization | DAO queries, indexes | Perf test | Covered |
| **NFR-ISU-006** | Custom exception hierarchy | `src/Exceptions/*` | UT-NFR-ISU-006 | Covered |
| **NFR-ISU-007** | @BABOK traceability | All source files | Code review (tag audit) | Covered |
| **NFR-ISU-008** | 100% test coverage | `tests/*` | PHPUnit coverage report | Covered |
| **NFR-ISU-009** | SOLID/DRY/DI | All code | Code review | Covered |

---

## 3. Business Requirements → Functional Requirements

| BR ID | Related FRs |
|-------|-------------|
| **BR-ISU-001** | FR-ISU-001-001 to FR-ISU-001-003, FR-ISU-002-001 to FR-ISU-002-003, FR-ISU-003-001 to FR-ISU-003-003, FR-ISU-004-001 to FR-ISU-004-002, FR-ISU-006-001 to FR-ISU-006-002, FR-ISU-007-001 to FR-ISU-007-002 |
| **BR-ISU-002** | FR-ISU-008-001 to FR-ISU-008-002, FR-ISU-009-001 to FR-ISU-009-002, FR-ISU-010-001 to FR-ISU-010-002, FR-ISU-011-001 to FR-ISU-011-002, FR-ISU-012-001 to FR-ISU-012-002, FR-ISU-013-001 to FR-ISU-013-002, FR-ISU-014-001 to FR-ISU-014-002, FR-ISU-016-001 to FR-ISU-016-002 |
| **BR-ISU-003** | FR-ISU-018-001 to FR-ISU-018-002, FR-ISU-019-001 to FR-ISU-019-002, FR-ISU-020-001 to FR-ISU-020-003 |
| **BR-ISU-004** | FR-ISU-021-001 to FR-ISU-021-003 |
| **BR-ISU-005** | FR-ISU-005-001 to FR-ISU-005-004 |
| **BR-ISU-006** | FR-ISU-017-001 to FR-ISU-017-003 |
| **BR-ISU-007** | NFR-ISU-007, NFR-ISU-008 |

---

## 4. Use Case Index

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

## 5. Test Index

| Test ID | Type | Tests | Related FR |
|---------|------|-------|------------|
| UT-ISU-001-001 | Unit | StagingCustomerDAO insert/find | FR-ISU-001-001 |
| UT-ISU-001-002 | Unit | StagingCustomer source field | FR-ISU-001-002 |
| UT-ISU-001-003 | Unit | StagingCustomer status field | FR-ISU-001-003 |
| UT-ISU-002-001 | Unit | StagingTransactionDAO insert/find | FR-ISU-002-001 |
| UT-ISU-002-002 | Unit | StagingTransaction fields | FR-ISU-002-002 |
| UT-ISU-002-003 | Unit | StagingTransaction source field | FR-ISU-002-003 |
| UT-ISU-003-001 | Unit | StagingPaymentDAO insert/find | FR-ISU-003-001 |
| UT-ISU-003-002 | Unit | StagingPayment fields | FR-ISU-003-002 |
| UT-ISU-003-003 | Unit | net_amount auto-calculation | FR-ISU-003-003 |
| UT-ISU-004-001 | Unit | StagingLineItemDAO insert/find | FR-ISU-004-001 |
| UT-ISU-004-002 | Unit | StagingLineItem transaction link | FR-ISU-004-002 |
| UT-ISU-005-001 | Unit | Duplicate transaction detection | FR-ISU-005-001 |
| UT-ISU-005-002 | Unit | Duplicate payment detection | FR-ISU-005-002 |
| UT-ISU-005-003 | Unit | Duplicate skip notification | FR-ISU-005-003 |
| UT-ISU-005-004 | Unit | stageOrUpdate idempotency | FR-ISU-005-004 |
| UT-ISU-006-001 | Unit | StagingMappingDAO insert/find | FR-ISU-006-001 |
| UT-ISU-006-002 | Unit | Mapping per source | FR-ISU-006-002 |
| UT-ISU-007-001 | Unit | StagingLogDAO insert | FR-ISU-007-001 |
| UT-ISU-007-002 | Unit | Log status/timestamp/error | FR-ISU-007-002 |
| UT-ISU-008-001 | Unit | Validators reject invalid records | FR-ISU-008-001 |
| UT-ISU-008-002 | Unit | StagingService validation gate | FR-ISU-008-002 |
| UT-ISU-009-001 | Unit | MatchingService matchCandidates | FR-ISU-009-001 |
| UT-ISU-009-002 | Unit | MatchConfig, Similarity scoring | FR-ISU-009-002 |
| UT-ISU-010-001 | Unit | MatchingService autoApprove threshold | FR-ISU-010-001 |
| UT-ISU-010-002 | Unit | MatchConfig threshold defaults | FR-ISU-010-002 |
| UT-ISU-011-001 | Unit | MatchingService needsReview threshold | FR-ISU-011-001 |
| UT-ISU-011-002 | Unit | MatchConfig review threshold | FR-ISU-011-002 |
| UT-ISU-012-001 | Unit | ProcessingPipeline processAll | FR-ISU-012-001 |
| UT-ISU-012-002 | Unit | createDebtorFromStaged CREATE_CUSTOMER | FR-ISU-012-002 |
| IT-ISU-012-002 | Integration | CREATE_CUSTOMER via hook_invoke_first | FR-ISU-012-002 |
| UT-ISU-013-001 | Unit | StagingService reconcilePayment | FR-ISU-013-001 |
| UT-ISU-013-002 | Unit | MatchingService calculatePaymentMatchScore | FR-ISU-013-002 |
| UT-ISU-014-001 | Unit | reconcilePayment auto-approve | FR-ISU-014-001 |
| UT-ISU-014-002 | Unit | reconcilePayment needs-review | FR-ISU-014-002 |
| UT-ISU-015-001 | Unit | StagingMappingDAO CRUD | FR-ISU-015-001 |
| UT-ISU-015-002 | Unit | pages/admin.php rendering | FR-ISU-015-002 |
| UT-ISU-016-001 | Unit | StagingLogDAO log entries | FR-ISU-016-001 |
| UT-ISU-016-002 | Unit | StagingService event logging | FR-ISU-016-002 |
| UT-ISU-017-001 | Unit | install.sql uses `0_` prefix | FR-ISU-017-001 |
| UT-ISU-017-002 | Unit | install.sql ALTER TABLE only | FR-ISU-017-002 |
| UT-ISU-017-003 | Unit | activate_extension check_table gate | FR-ISU-017-003 |
| UT-ISU-018-001 | Unit | getModuleConstants returns constants | FR-ISU-018-001 |
| UT-ISU-018-002 | Unit | getModuleConstants module name + caps | FR-ISU-018-002 |
| UT-ISU-019-001 | Unit | getModuleCapabilities returns defs | FR-ISU-019-001 |
| UT-ISU-019-002 | Unit | getModuleCapabilities methods + events | FR-ISU-019-002 |
| UT-ISU-020-001 | Unit | hasCapability reports support | FR-ISU-020-001 |
| UT-ISU-020-002 | Unit | respondToCapabilityRequest dispatches | FR-ISU-020-002 |
| UT-ISU-020-003 | Unit | respondToCapabilityRequest request types | FR-ISU-020-003 |
| UT-ISU-021-001 | Unit | SourceConfig construction + getters | FR-ISU-021-001 |
| UT-ISU-021-002 | Unit | SourceConfig GL/bank/payment values | FR-ISU-021-002 |
| UT-ISU-021-003 | Unit | SourceConfig with* fluent setters | FR-ISU-021-003 |
| UT-NFR-ISU-006 | Unit | Exception hierarchy factory methods | NFR-ISU-006 |

---

## 6. UAT Tests (via curl / chromium)

| UAT ID | UAT Name | FR IDs | Test Method |
|--------|----------|--------|-------------|
| UAT-ISU-001 | Upload CSV and verify staging records | FR-ISU-001, FR-ISU-003, FR-ISU-004 | curl POST to import page |
| UAT-ISU-002 | Import via API and verify transactions staged | FR-ISU-002, FR-ISU-003 | curl POST to API endpoint |
| UAT-ISU-003 | Process staged transactions into FA invoices | FR-ISU-005, FR-ISU-006 | curl POST to process endpoint |
| UAT-ISU-004 | Duplicate detection prevents re-import | FR-ISU-005 | curl POST (same transaction_id twice) |
| UAT-ISU-005 | Counterparty matching links to existing customers | FR-ISU-006 | curl GET match endpoint |
| UAT-ISU-006 | Error handling for malformed data | FR-ISU-001, FR-ISU-007 | curl POST malformed payload |
| UAT-ISU-007 | Process queue displays correct status | FR-ISU-005, FR-ISU-007 | curl GET queue endpoint |
