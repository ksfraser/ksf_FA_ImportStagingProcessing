# Test Plan — ksf_FA_ImportStagingProcessing

## 1. Test Strategy

### 1.1 Approach
- **TDD (Test-Driven Development)**: Red-Green-Refactor cycle
- **PHPUnit 9.6+** for unit and integration tests
- **100% coverage target** for all classes and methods
- Mock external dependencies (ksf_ModulesDAO, FA DB)
- Test all boundary conditions, error scenarios, and invalid inputs

### 1.2 Test Levels
| Level | Scope | Tool |
|-------|-------|------|
| Unit | Individual classes/methods in isolation | PHPUnit + mocks |
| Integration | DAO with test database, Service with real dependencies | PHPUnit |
| UAT | End-to-end flow: stage → validate → match → process | Manual + scripted |

### 1.3 Environment
- PHP 7.3+ (matching production target)
- MySQL/MariaDB test database
- ksf_ModulesDAO for DB abstraction
- ksfraser/famock for FA mock routines

---

## 2. Unit Tests

### 2.1 Models/DTOs
| Test Class | Tests |
|------------|-------|
| `StagingCustomerTest` | Getters/setters, serialization, validation, required fields |
| `StagingTransactionTest` | Getters/setters, serialization, status transitions |
| `StagingMappingTest` | Field mapping, transforms, defaults |
| `ProcessingResultTest` | Success/failure states, error collection |

### 2.2 Services
| Test Class | Tests |
|------------|-------|
| `StagingServiceTest` | Stage customer, stage transaction, status updates, duplicate detection |
| `MatchingServiceTest` | Score calculation, auto-approve threshold, review threshold, no-match |
| `ProcessingPipelineTest` | Pipeline execution, error handling, event emission |

### 2.3 Validators
| Test Class | Tests |
|------------|-------|
| `TransactionValidatorTest` | Required fields, amount format, date format, source validation |
| `CustomerValidatorTest` | Required fields, email format, phone format |

### 2.4 Exceptions
| Test Class | Tests |
|------------|-------|
| `StagingExceptionTest` | Factory methods, message format, exception hierarchy |

---

## 3. Integration Tests

### 3.1 DAO Tests
| Test Class | Tests |
|------------|-------|
| `StagingCustomerDAOTest` | CRUD operations, findBySource, findByStatus, duplicate handling |
| `StagingTransactionDAOTest` | CRUD, findBySource, findByStatus, findByDateRange |
| `StagingMappingDAOTest` | CRUD, findBySource, getMapping |
| `StagingLogDAOTest` | CRUD, findByAction, findByRecord, date range filtering |
| `SchemaMigrationTest` | ensureTableExists(), ALTER TABLE column addition |

### 3.2 Service Integration
| Test Class | Tests |
|------------|-------|
| `StagingServiceIntegrationTest` | Full stage → validate → process flow with test DB |
| `MatchingServiceIntegrationTest` | Real matching against test data |

---

## 4. Test Cases

### TC-01: Staging Management

**TC-01.01**: Stage a customer from WooCommerce source
- **Given**: Valid customer data from WooCommerce
- **When**: `stageCustomer()` is called
- **Then**: Record exists in `staging_customers` with source='woocommerce', status='staged'

**TC-01.02**: Stage a transaction from Square API source
- **Given**: Valid transaction data from Square API
- **When**: `stageTransaction()` is called
- **Then**: Record exists in `staging_transactions` with source='square_api', status='staged'

**TC-01.03**: Reject duplicate transaction by source ID
- **Given**: Existing transaction with same source_transaction_id
- **When**: `stageTransaction()` is called
- **Then**: DuplicateTransactionException thrown

**TC-01.04**: Update status from staged to processed
- **Given**: Staged record exists
- **When**: `updateStatus()` is called with 'processed'
- **Then**: Record status updated, log entry created

### TC-02: Processing Pipeline

**TC-02.01**: Validate a valid transaction record
- **Given**: Complete transaction data
- **When**: `validate()` is called
- **Then**: ValidationResult has success=true

**TC-02.02**: Reject invalid transaction record
- **Given**: Missing required fields (amount, date)
- **When**: `validate()` is called
- **Then**: ValidationResult has success=false with appropriate errors

**TC-02.03**: Auto-approve match with >= 95% confidence
- **Given**: Staged transaction matching existing FA transaction exactly
- **When**: `matchTransactions()` is called
- **Then**: MatchResult has status='matched', confidence >= 0.95

**TC-02.04**: Flag match for review at 80-95% confidence
- **Given**: Staged transaction with partial match to FA transaction
- **When**: `matchTransactions()` is called
- **Then**: MatchResult has status='needs_review', 0.80 <= confidence < 0.95

### TC-03: Inter-Module Communication

**TC-03.01**: getModuleConstants returns expected constants
**TC-03.02**: getModuleCapabilities includes 'staging' capability
**TC-03.03**: hasCapability('staging') returns true
**TC-03.04**: respondToCapabilityRequest routes correctly

### TC-04: Event-Driven Architecture

**TC-04.01**: 'staged' event emitted when record is staged
**TC-04.02**: 'processed' event emitted when record is processed
**TC-04.03**: Events logged in staging_log

### TC-05: Backward Compatibility

**TC-05.01**: ensureTableExists adds missing columns without data loss
**TC-05.02**: Existing prod data readable after schema migration

---

## 5. Coverage Requirements

| Component | Coverage Target |
|-----------|-----------------|
| Contracts (interfaces) | 100% (via implementation tests) |
| Models/DTOs | 100% |
| Services | 100% |
| Validators | 100% |
| Exceptions | 100% |
| DAO (integration) | 100% of CRUD operations |
| hooks.php | 100% of 4 standard methods |

---

## 6. Test Execution

```bash
# Run all tests
composer test

# Run with coverage
phpunit --coverage-html coverage/

# Run specific test suite
phpunit tests/Unit
phpunit tests/Integration
```
