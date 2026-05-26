# Requirements Traceability Matrix (RTM) — ksf_FA_ImportStagingProcessing

## Matrix Legend
- **FR**: Functional Requirement
- **NFR**: Non-Functional Requirement
- **UC**: Use Case
- **TC**: Test Case

---

## RTM: Requirement -> Use Case -> Code File -> Test

| Req ID | Requirement Summary | Use Case | Code File(s) | Test |
|--------|--------------------|----------|--------------|------|
| **FR-01.01** | Unified staging_customers table | UC-01: Stage Customer | `src/DAO/StagingCustomerDAO.php`, `sql/install.sql` | TC-01.01 |
| **FR-01.02** | Unified staging_transactions table | UC-02: Stage Transaction | `src/DAO/StagingTransactionDAO.php`, `sql/install.sql` | TC-01.02 |
| **FR-01.03** | staging_mapping config table | UC-05: Configure Mapping | `src/DAO/StagingMappingDAO.php`, `sql/install.sql` | TC-01.03 |
| **FR-01.04** | staging_log audit table | UC-03: Audit Trail | `src/DAO/StagingLogDAO.php`, `sql/install.sql` | TC-01.04 |
| **FR-01.05** | Accept records from all sources | UC-01, UC-02 | `src/Services/StagingService.php` | TC-01.05 |
| **FR-01.06** | Per-record status tracking | UC-01, UC-02 | `src/Models/StagingTransaction.php` | TC-01.06 |
| **FR-01.07** | Backward-compatible ALTER TABLE | UC-06: Upgrade | `sql/install.sql`, DAO ensureTableExists() | TC-01.07 |
| **FR-02.01** | Validate staged records | UC-02 | `src/Validators/TransactionValidator.php` | TC-02.01 |
| **FR-02.02** | Match staged vs FA transactions | UC-04: Match | `src/Services/MatchingService.php` | TC-02.02 |
| **FR-02.03** | Auto-approve >= 95% confidence | UC-04 | `src/Services/MatchingService.php` | TC-02.03 |
| **FR-02.04** | Flag 80-95% for review | UC-04 | `src/Services/MatchingService.php` | TC-02.04 |
| **FR-02.05** | Process approved matches | UC-04 | `src/Services/ProcessingPipeline.php` | TC-02.05 |
| **FR-02.06** | Log all processing steps | UC-03 | `src/DAO/StagingLogDAO.php` | TC-02.06 |
| **FR-03.01** | Implement 4 standard hook methods | UC-05 | `hooks.php` | TC-03.01 |
| **FR-03.02** | Expose staging capability | UC-05 | `hooks.php`, `src/Services/StagingService.php` | TC-03.02 |
| **FR-03.03** | Provide DAO for direct access | UC-05 | `src/DAO/*` | TC-03.03 |
| **FR-03.04** | Support hooks API + DAO access | UC-05 | `hooks.php`, `src/DAO/*` | TC-03.04 |
| **FR-04.01** | Emit staging lifecycle events | UC-01, UC-02, UC-04 | `src/Services/StagingService.php` | TC-04.01 |
| **FR-04.02** | Allow event subscription | UC-01, UC-02, UC-04 | `src/Services/StagingService.php` | TC-04.02 |
| **FR-04.03** | Log all events | UC-03 | `src/DAO/StagingLogDAO.php` | TC-04.03 |
| **FR-05.01** | Field mapping per source | UC-05 | `src/DAO/StagingMappingDAO.php` | TC-05.01 |
| **FR-05.02** | Staging management UI | UC-05 | `pages/*` | TC-05.02 |
| **FR-05.03** | Error resolution interface | UC-05 | `pages/*` | TC-05.03 |
| **FR-05.04** | Error logging with context | UC-03 | `src/DAO/StagingLogDAO.php` | TC-05.04 |
| **FR-06.01** | staging_payments unified table | UC-07: Stage Payment | `sql/install.sql`, `src/DAO/StagingPaymentDAO.php` | TC-06.01 |
| **FR-06.02** | staging_payment_matches audit table | UC-08: Reconcile Payment | `sql/install.sql`, `src/DAO/StagingPaymentMatchDAO.php` | TC-06.02 |
| **FR-06.03** | Stage individual payment tenders | UC-07 | `src/Models/StagingPayment.php` | TC-06.03 |
| **FR-06.04** | Auto-calculate net_amount | UC-07 | `src/Services/StagingService.php` | TC-06.04 |
| **FR-06.05** | Link payments to staging_transactions | UC-07 | `src/Models/StagingPayment.php` | TC-06.05 |
| **FR-06.06** | Payment matching scoring | UC-08 | `src/Services/MatchingService.php` | TC-06.06 |
| **FR-06.07** | Auto-reconcile >= 95% | UC-08 | `src/Services/StagingService.php` | TC-06.07 |
| **FR-06.08** | Flag 80-95% for review | UC-08 | `src/Services/StagingService.php` | TC-06.08 |
| **FR-06.09** | Reconcile queue processing | UC-08 | `src/Services/StagingService.php` | TC-06.09 |
| **FR-06.10** | Payment status lifecycle | UC-07, UC-08 | `src/Models/StagingPayment.php` | TC-06.10 |
| **NFR-01** | PHP >=7.3 compatibility | All | `composer.json` | CI |
| **NFR-02** | FA 2.4.x integration | All | `hooks.php` | Manual |
| **NFR-03** | TB_PREF convention | All | All SQL queries | Code review |
| **NFR-04** | Security best practices | All | All code | Code review |
| **NFR-05** | Performance optimization | All | DAO queries, indexes | Perf test |
| **NFR-06** | Custom exception hierarchy | All | `src/Exceptions/*` | TC-NFR-06 |
| **NFR-07** | 100% test coverage | All | `tests/*` | CI |
| **NFR-08** | SOLID/DRY/DI | All | All code | Code review |

---

## Use Case Index

| UC ID | Name | Trigger | Primary Actor |
|-------|------|---------|---------------|
| UC-01 | Stage Customer | Source module provides customer data | Source Module (WooCommerce/Square) |
| UC-02 | Stage Transaction | Source module provides transaction data | Source Module (WooCommerce/Square/PayPal/Bank) |
| UC-03 | Audit Trail | Any staging/processing operation | System (automatic) |
| UC-04 | Match and Process Transactions | User or system initiates processing | FA Administrator / System |
| UC-05 | Configure Mappings | Administrator sets up field mapping | FA Administrator |
| UC-06 | Upgrade Staging Schema | Module installation/update | System (automatic) |
| UC-07 | Stage Payment | Source module provides payment data | Source Module (WooCommerce/Square) |
| UC-08 | Reconcile Payment | User or system initiates reconciliation | FA Administrator / System |
