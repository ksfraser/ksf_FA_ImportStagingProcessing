# Architecture — ksf_FA_ImportStagingProcessing

## 1. Layered Architecture

```
┌──────────────────────────────────────────────────────────────┐
│               Presentation Layer (pages/)                     │
│  FA UI pages for staging management, review, reconciliation   │
├──────────────────────────────────────────────────────────────┤
│               Business Logic Layer (src/)                     │
│  Contracts/   — Interfaces for DI and polymorphism            │
│  Services/    — StagingService, MatchingService, Pipeline     │
│  Models/      — DTOs: StagingCustomer, StagingTransaction     │
│  Validators/  — Input validation rules                        │
│  Exceptions/  — Custom exception hierarchy                    │
├──────────────────────────────────────────────────────────────┤
│               Data Access Layer (src/DAO/)                    │
│  DAO classes via ksf_ModulesDAO abstraction                   │
│  StagingCustomerDAO, StagingTransactionDAO                    │
│  StagingMappingDAO, StagingLogDAO                             │
├──────────────────────────────────────────────────────────────┤
│               Infrastructure Layer                            │
│  hooks.php        — FA hooks integration (4 standard methods) │
│  ksf_import_staging.php — Module entry point                  │
│  composer.json    — PSR-4 autoloading                         │
│  sql/install.sql  — Database schema                           │
│  tests/           — PHPUnit tests                             │
└──────────────────────────────────────────────────────────────┘
```

## 2. Design Patterns

### Strategy Pattern
- **Processors**: Each source type (WooCommerce, Square, PayPal, Bank) has its own processor implementing `ProcessorInterface`
- **Matching**: Pluggable match strategies (amount, date, customer)

### Factory Pattern
- Service creation with dependency injection

### Repository Pattern
- DAO classes for data access abstraction via `ksf_ModulesDAO`

### Observer Pattern (Event-Driven)
- Staging lifecycle events: staged, validated, matched, processed, reconciled
- Other modules subscribe via hooks or event listeners

### DTO Pattern
- `StagingCustomer`, `StagingTransaction`, `StagingMapping` as data transfer objects

### Polymorphism over Conditionals
- Use SRP classes and polymorphism instead of if/then/else/switch

---

## 3. Event-Driven Architecture

### Events
| Event | Trigger | Payload |
|-------|---------|---------|
| `staging.customer.staged` | Customer record inserted | StagingCustomer |
| `staging.transaction.staged` | Transaction record inserted | StagingTransaction |
| `staging.record.validated` | Record validated | Record + ValidationResult |
| `staging.record.matched` | Match found | Record + MatchResult |
| `staging.record.processed` | Record processed into FA | Record + ProcessingResult |
| `staging.record.reconciled` | Record reconciled | Record + ReconciliationResult |

### Subscription
```php
// Other modules can subscribe:
hook_invoke('ksf_FA_ImportStagingProcessing', 'on', $data, [
    'event' => 'staging.transaction.staged',
    'callback' => 'myEventHandler'
]);
```

---

## 4. Processing Pipeline

```
[Source Module]
     │
     ▼
┌─────────────┐
│  Stage       │  → staging_transactions/staging_customers
│  (insert)    │  → status = 'staged'
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Validate    │  → ValidationService
│  (rules)     │  → status = 'validated' or 'failed'
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Match       │  → MatchingService (scoring)
│  (compare)   │  → confidence >= 0.95: auto-approve
│              │  → 0.80 <= conf < 0.95: needs_review
│              │  → conf < 0.80: unmatched
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Process     │  → ProcessingPipeline
│  (create FA) │  → Creates/matches FA entities
│              │  → status = 'processed' or 'failed'
└──────┬──────┘
       │
       ▼
┌─────────────┐
│  Audit       │  → staging_log
│  (log)       │  → All actions recorded
└─────────────┘
```

---

## 5. Module Communication

### Hooks API (Standard 4 Methods)
```php
// Discovery
hook_invoke('ksf_FA_ImportStagingProcessing', 'getModuleConstants', $data);
hook_invoke('ksf_FA_ImportStagingProcessing', 'getModuleCapabilities', $data);
hook_invoke('ksf_FA_ImportStagingProcessing', 'hasCapability', $data, ['capability' => 'staging']);
hook_invoke('ksf_FA_ImportStagingProcessing', 'respondToCapabilityRequest', $data, ['request' => 'staging:stageTransaction']);

// Staging Operations (via hooks)
hook_invoke('ksf_FA_ImportStagingProcessing', 'stageTransaction', $data, ['transaction' => [...], 'source' => 'woocommerce']);
hook_invoke('ksf_FA_ImportStagingProcessing', 'stageCustomer', $data, ['customer' => [...], 'source' => 'square_api']);
```

### Direct DAO Access (Composer Package)
```php
use Ksfraser\ImportStaging\DAO\StagingTransactionDAO;

$dao = new StagingTransactionDAO($tablePrefix);
$dao->insert($transactionData);
```

---

## 6. Backward Compatibility Strategy

For existing production tables (`0_ksf_import_square_transactions`, etc.):
1. **NEVER DROP or TRUNCATE** existing tables
2. Use ALTER TABLE to add missing columns (via DAO `ensureTableExists()`)
3. New unified tables coexist with legacy tables
4. Migration path: legacy → new via data copy (optional, user-initated)

```php
// StagingTransactionDAO::ensureTableExists()
$sql = "ALTER TABLE {$this->tableName} ADD COLUMN source VARCHAR(16) NOT NULL DEFAULT 'square_api'";
$this->db->query($sql); // Ignores if column already exists
```
