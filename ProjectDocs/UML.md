# UML Documentation — ksf_FA_ImportStagingProcessing

## 1. Component Diagram: Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                   FrontAccounting 2.4.x                          │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │              ksf_FA_ImportStagingProcessing                   │ │
│  │                                                               │ │
│  │  ┌─────────────────────────────────────────────────────────┐ │ │
│  │  │  Presentation Layer (pages/)                             │ │ │
│  │  │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  │ │ │
│  │  │  │  Dashboard   │  │  Staging Mgr │  │  Match/Review│  │ │ │
│  │  │  └──────────────┘  └──────────────┘  └──────────────┘  │ │ │
│  │  └─────────────────────────────────────────────────────────┘ │ │
│  │                                                               │ │
│  │  ┌─────────────────────────────────────────────────────────┐ │ │
│  │  │  Business Logic Layer (src/)                             │ │ │
│  │  │  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌────────────┐ │ │ │
│  │  │  │Contracts │ │ Services │ │ Validator│ │ Models     │ │ │ │
│  │  │  │/Interfaces│ │/Pipeline │ │ /Input   │ │ /DTOs      │ │ │ │
│  │  │  └──────────┘ └──────────┘ └──────────┘ └────────────┘ │ │ │
│  │  │  ┌──────────┐ ┌──────────┐ ┌─────────────────────────┐ │ │ │
│  │  │  │  DAO     │ │Exceptions│ │  Events/Observer        │ │ │ │
│  │  │  └──────────┘ └──────────┘ └─────────────────────────┘ │ │ │
│  │  └─────────────────────────────────────────────────────────┘ │ │
│  │                                                               │ │
│  │  ┌─────────────────────────────────────────────────────────┐ │ │
│  │  │  Data Access Layer                                       │ │ │
│  │  │  staging_customers, staging_transactions,                │ │ │
│  │  │  staging_mapping, staging_log                             │ │ │
│  │  └─────────────────────────────────────────────────────────┘ │ │
│  │                                                               │ │
│  │  ┌─────────────────────────────────────────────────────────┐ │ │
│  │  │  Infrastructure Layer                                    │ │ │
│  │  │  hooks.php, ksf_import_staging.php, composer.json        │ │ │
│  │  └─────────────────────────────────────────────────────────┘ │ │
│  └─────────────────────────────────────────────────────────────┘ │
│                                                                   │
│  PHP 7.3+ | MySQL/MariaDB | PSR-4 Autoloading                     │
└─────────────────────────────────────────────────────────────────┘
                     │
     ┌───────────────┼───────────────┬───────────────┐
     ▼               ▼               ▼               ▼
┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────────┐
│WooCommerce│  │Square API│  │Square CSV│  │PayPal/Bank   │
│(ksf_gen)  │  │(ksf_Sq)  │  │(FA_ImpSq)│  │(FA_ImpSq/Bnk)│
└──────────┘  └──────────┘  └──────────┘  └──────────────┘
```

---

## 2. Class Diagram: Core Domain

```
┌──────────────────────────────────────────────────────┐
│ <<interface>>                                        │
│ StagingManagerInterface                              │
├──────────────────────────────────────────────────────┤
│ + stageCustomer(data, source): StagingCustomer       │
│ + stageTransaction(data, source): StagingTransaction │
│ + getStagedCustomers(filters): array                 │
│ + getStagedTransactions(filters): array              │
│ + updateStatus(id, status, error): void              │
│ + processQueue(source): ProcessingResult             │
└──────────────────────────┬───────────────────────────┘
                           │ implements
┌──────────────────────────┴───────────────────────────┐
│  StagingService                                      │
├──────────────────────────────────────────────────────┤
│ - customerDAO: StagingCustomerDAO                    │
│ - transactionDAO: StagingTransactionDAO              │
│ - logDAO: StagingLogDAO                              │
│ - validators: TransactionValidatorInterface[]         │
│ - eventEmitter: EventEmitterTrait                    │
├──────────────────────────────────────────────────────┤
│ + stageCustomer(data, source): StagingCustomer       │
│ + stageTransaction(data, source): StagingTransaction │
│ + validate(record): ValidationResult                 │
│ + processQueue(source): ProcessingResult             │
│ + addValidator(validator): void                      │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ <<interface>>                                        │
│ ProcessorInterface                                   │
├──────────────────────────────────────────────────────┤
│ + process(record, context): ProcessingResult         │
│ + canProcess(record): bool                           │
└──────────────────────────┬───────────────────────────┘
                           │ implements
┌──────────────────────────┴───────────────────────────┐
│  CustomerProcessor                                   │
│  TransactionProcessor                                │
│  (Strategy pattern per source type)                  │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ <<interface>>                                        │
│ MatchingServiceInterface                             │
├──────────────────────────────────────────────────────┤
│ + matchCandidates(staged, existing): MatchResult[]   │
│ + autoApprove(confidence): bool                      │
│ + needsReview(confidence): bool                      │
│ + approveMatch(matchId): void                        │
│ + rejectMatch(matchId, reason): void                 │
└──────────────────────────┬───────────────────────────┘
                           │ implements
┌──────────────────────────┴───────────────────────────┐
│  MatchingService                                     │
├──────────────────────────────────────────────────────┤
│ - autoApproveThreshold: float (default 0.95)         │
│ - reviewThreshold: float (default 0.80)              │
│ - matchers: MatchStrategyInterface[]                  │
├──────────────────────────────────────────────────────┤
│ + matchByAmount(amountA, amountB): float             │
│ + matchByDate(dateA, dateB, tolerance): float        │
│ + matchByCustomer(custA, custB): float               │
│ + calculateConfidence(scores): float                 │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│ <<interface>>                                        │
│ ValidationServiceInterface                           │
├──────────────────────────────────────────────────────┤
│ + validate(record): ValidationResult                 │
│ + getRules(): ValidationRule[]                       │
└──────────────────────────┬───────────────────────────┘
                           │ implements
┌──────────────────────────┴───────────────────────────┐
│  TransactionValidator                                │
│  CustomerValidator                                   │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│  Models / DTOs                                       │
├──────────────────────────────────────────────────────┤
│  StagingCustomer                                     │
│  ├── id, source, sourceId, name, email, phone        │
│  ├── address, faDebtorNo, status                     │
│  └── createdAt, updatedAt                            │
│                                                       │
│  StagingTransaction                                  │
│  ├── id, source, sourceId, transactionDate           │
│  ├── totalAmount, taxAmount, currency                │
│  ├── customerId, customerName, rawJson               │
│  ├── status, errorLog, faInvoiceNo                   │
│  └── createdAt, updatedAt                            │
│                                                       │
│  StagingMapping (DTO)                                │
│  ├── id, source, sourceField, targetField            │
│  ├── transform, defaultValue                         │
│  └── createdAt                                       │
│                                                       │
│  ProcessingResult (Value Object)                     │
│  ├── success, recordId, action                       │
│  ├── faReferenceNo, errors[]                         │
│  └── matchedAt, processedAt                          │
└──────────────────────────────────────────────────────┘

┌──────────────────────────────────────────────────────┐
│  Exceptions                                          │
├──────────────────────────────────────────────────────┤
│ StagingException extends RuntimeException            │
│   + notFound(id): self                              │
│   + invalidSource(source): self                      │
│   + duplicateTransaction(id): self                   │
│   + mappingFailed(source, field): self               │
│   + processingFailed(reason): self                   │
│                                                       │
│ StagingNotFoundException                             │
│ InvalidSourceException                               │
│ DuplicateTransactionException                        │
│ MappingException                                     │
│ ProcessingException                                  │
└──────────────────────────────────────────────────────┘
```

---

## 3. Sequence Diagram: Stage Transaction

```
SourceModule        StagingService        TransactionValidator    StagingTransactionDAO    StagingLogDAO
    │                     │                       │                      │                    │
    │  stageTransaction   │                       │                      │                    │
    │  (data, source)     │                       │                      │                    │
    │────────────────────>│                       │                      │                    │
    │                     │  validate(data)       │                      │                    │
    │                     │──────────────────────>│                      │                    │
    │                     │<── ValidationResult ──│                      │                    │
    │                     │                       │                      │                    │
    │                     │  create DTO from data │                      │                    │
    │                     │  StagingTransaction   │                      │                    │
    │                     │                       │                      │                    │
    │                     │  insert(transaction)  │                      │                    │
    │                     │─────────────────────────────────────────────>│                    │
    │                     │<── id ───────────────────────────────────────│                    │
    │                     │                       │                      │                    │
    │                     │  log('staged', id)    │                      │                    │
    │                     │────────────────────────────────────────────────────────────────>│
    │                     │                       │                      │                    │
    │                     │  emit('staged', id)   │                      │                    │
    │                     │                       │                      │                    │
    │<── StagingTransaction───────────────────────│                      │                    │
```

---

## 4. Sequence Diagram: Match and Process

```
Admin/System        MatchingService        ProcessingPipeline     StagingTransactionDAO    StagingLogDAO
    │                     │                       │                      │                    │
    │  processQueue(src)  │                       │                      │                    │
    │────────────────────>│                       │                      │                    │
    │                     │  getStaged(status=    │                      │                    │
    │                     │  'staged', source)    │                      │                    │
    │                     │─────────────────────────────────────────────>│                    │
    │                     │<── records ──────────────────────────────────│                    │
    │                     │                       │                      │                    │
    │                     │  (loop per record)    │                      │                    │
    │                     │                       │                      │                    │
    │                     │  match(record)        │                      │                    │
    │                     │──────────────────────>│                      │                    │
    │                     │<── MatchResult ───────│                      │                    │
    │                     │                       │                      │                    │
    │                     │  if confidence >=     │                      │                    │
    │                     │  0.95: auto-approve   │                      │                    │
    │                     │                       │                      │                    │
    │                     │  if 0.80 <= conf <    │                      │                    │
    │                     │  0.95: flag review    │                      │                    │
    │                     │                       │                      │                    │
    │                     │  updateStatus(id,     │                      │                    │
    │                     │  matched/needs_review)│                      │                    │
    │                     │─────────────────────────────────────────────>│                    │
    │                     │                       │                      │                    │
    │                     │  log(match result)    │                      │                    │
    │                     │────────────────────────────────────────────────────────────────>│
    │<── ProcessingResult─│                       │                      │                    │
```

---

## 5. Database Schema: Unified Staging Tables

```
┌──────────────────────────────────────┐
│  staging_customers                   │
├──────────────────────────────────────┤
│ PK: id                               │
│ source (woocommerce/square/paypal/   │
│        bank)                         │
│ source_customer_id                   │
│ name, email, phone                   │
│ address_line1, city, province        │
│ postal_code, country                 │
│ raw_json (LONGTEXT)                  │
│ status (staged/validated/            │
│         matched/processed/failed)    │
│ fa_debtor_no                         │
│ error_log                            │
│ created_at / updated_at              │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  staging_transactions                │
├──────────────────────────────────────┤
│ PK: id                               │
│ source (woocommerce/square/paypal/   │
│        bank)                         │
│ source_transaction_id                │
│ source_order_id                      │
│ source_payment_id                    │
│ transaction_date                     │
│ total_amount, tax_amount             │
│ tip_amount, discount_amount          │
│ currency                             │
│ customer_name                        │
│ customer_email                       │
│ raw_json (LONGTEXT)                  │
│ status (staged/validated/            │
│         matched/processed/failed)    │
│ match_confidence                     │
│ fa_invoice_no                        │
│ fa_debtor_no                         │
│ error_log                            │
│ created_at / updated_at              │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  staging_mapping                     │
├──────────────────────────────────────┤
│ PK: id                               │
│ source                               │
│ source_field                         │
│ target_field                         │
│ transform (none/normalize/map)       │
│ default_value                        │
│ is_required (bool)                   │
│ created_at / updated_at              │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  staging_log                         │
├──────────────────────────────────────┤
│ PK: id                               │
│ record_type (customer/transaction)   │
│ record_id                            │
│ action (staged/validated/matched/    │
│         processed/failed)            │
│ source                               │
│ details                              │
│ created_at                           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  staging_payments                    │
├──────────────────────────────────────┤
│ PK: id                               │
│ source (woocommerce/square/paypal/   │
│        bank)                         │
│ source_payment_id                    │
│ source_transaction_id                │
│ staging_transaction_id (FK)          │
│ amount, currency                     │
│ fee, net_amount                      │
│ payment_method (credit_card/cash/    │
│                gift_card/check/other)│
│ payment_date                         │
│ reference                            │
│ card_brand, pan_suffix               │
│ card_entry_method                    │
│ raw_json (LONGTEXT)                  │
│ status (staged/validated/matched/    │
│         reconciled/failed)           │
│ match_confidence                     │
│ fa_trans_type, fa_trans_no           │
│ fa_bank_account                      │
│ error_log                            │
│ created_at / updated_at              │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│  staging_payment_matches             │
├──────────────────────────────────────┤
│ PK: id                               │
│ staging_payment_id (FK)              │
│ match_type (exact/fuzzy/partial/none)│
│ match_confidence                     │
│ fa_trans_type, fa_trans_no           │
│ fa_bank_account                      │
│ match_status (matched/needs_review/  │
│              rejected)               │
│ matched_by (system/user)             │
│ notes                                │
│ created_at / updated_at              │
└──────────────────────────────────────┘
```

---

## 6. Cross-Module Hooks+DTO Architecture

External modules (Square, WooCommerce, PayPal, Stripe) call ISU hooks with
DTOs from `ksfraser/staging-dto`. ISU handles all DB operations.

### Class Diagram: DTO Hierarchy

```
┌─────────────────────────────────────────────────────────┐
│  ksfraser/staging-dto (shared package)                    │
│                                                          │
│  «abstract» StagingEntity                                │
│  ├─────────────────────────────────────────────────────│
│  │ # version: string (each DTO sets in constructor)    │
│  │ # source: string                                     │
│  │ # sourceId: string                                   │
│  │ # entityType: string                                 │
│  │ # rawJson: string                                    │
│  │ # status: string                                     │
│  └──────────────────────────────────────────────────────┘
│           │                                              │
│           ├── «abstract» StagingTransaction              │
│           │   ├── StagingOrder        (POS)              │
│           │   ├── StagingInvoice      (remote)           │
│           │   ├── StagingPayment      (payment record)   │
│           │   ├── StagingRefund       (refund)           │
│           │   └── StagingSubscription (recurring)        │
│           │                                              │
│           ├── StagingCustomer                            │
│           ├── StagingProduct                             │
│           │   └── StagingProductVariant                  │
│           ├── StagingCategory                            │
│           ├── StagingTax                                 │
│           ├── StagingDiscount                            │
│           ├── StagingCoupon                              │
│           ├── StagingLoyaltyProgram                      │
│           ├── StagingLoyaltyReward                       │
│           ├── StagingLoyaltyAccount                      │
│           ├── StagingInventory                           │
│           ├── StagingShipment                            │
│           ├── StagingNote                                │
│           └── StagingLineItem (has transactionSourceId)  │
│                                                          │
│  «value object» StagingExistsQuery                       │
│  ├─────────────────────────────────────────────────────│
│  │ + source: string                                     │
│  │ + sourceId: string                                   │
│  │ + entityType: string                                 │
│  └──────────────────────────────────────────────────────┘
│                                                          │
│  «value object» StagingExistsResult                      │
│  ├─────────────────────────────────────────────────────│
│  │ + exists: bool                                       │
│  │ + stagingId: ?int                                    │
│  │ + status: ?string                                    │
│  └──────────────────────────────────────────────────────┘
└─────────────────────────────────────────────────────────┘
```

### Sequence Diagram: Hook+DTO Integration Flow

```
  Source Module (Square)         ISU hooks.php             ISU StagingService          FA DB
       │                              │                        │                        │
       │  $dto = new StagingOrder()   │                        │                        │
       │  $dto->setSource('square')   │                        │                        │
       │  $dto->setSourceId('sq_123') │                        │                        │
       │                              │                        │                        │
       │  hook_invoke('ksf_FA_ImportStagingProcessing',        │                        │
       │    'stageEntity', $dto)      │                        │                        │
       │─────────────────────────────>│                        │                        │
       │                              │  stageEntity($dto)     │                        │
       │                              │───────────────────────>│                        │
       │                              │                        │  $dto->getVersion()    │
       │                              │                        │  $dto instanceof       │
       │                              │                        │  StagingOrder          │
       │                              │                        │                        │
       │                              │                        │  db_escape(values)     │
       │                              │                        │───────────────────────>│
       │                              │                        │  db_query(INSERT)      │
       │                              │                        │───────────────────────>│
       │                              │                        │  db_insert_id()        │
       │                              │                        │<───────────────────────│
       │  return int (staging_id)     │                        │                        │
       │<─────────────────────────────│                        │                        │
```

### Sequence Diagram: Staging Exists Check

```
  Source Module (Square)         ISU hooks.php             ISU StagingService          FA DB
       │                              │                        │                        │
       │  $query = new StagingExists-  │                        │                        │
       │    Query('square','sq_123',   │                        │                        │
       │    'transaction')             │                        │                        │
       │                              │                        │                        │
       │  hook_invoke(...,             │                        │                        │
       │    'stagingExists', $query)   │                        │                        │
       │─────────────────────────────>│                        │                        │
       │                              │  stagingExists($query)  │                        │
       │                              │───────────────────────>│                        │
       │                              │                        │  db_query(SELECT)      │
       │                              │                        │───────────────────────>│
       │                              │                        │  db_fetch_assoc()      │
       │                              │                        │<───────────────────────│
       │  return StagingExistsResult   │                        │                        │
       │  {exists: true, id: 42,      │                        │                        │
       │   status: 'staged'}          │                        │                        │
       │<─────────────────────────────│                        │                        │
```

### Data Flow: Multi-Source Import via Hooks+DTO

```
┌──────────────┐     ┌─────────────────┐     ┌───────────────────┐
│ Square API   │────>│ StagingOrder    │────>│ ISU hook_invoke   │
│              │     │ StagingCustomer │     │ 'stageEntity'     │
│              │     │ StagingPayment  │     │                   │
│              │     │ StagingLineItem │     │ ISU handles:      │
│              │     │                 │     │ - DB inserts      │
│              │     │                 │     │ - Matching        │
│              │     │                 │     │ - FA entity creation│
└──────────────┘     └─────────────────┘     └───────────────────┘

┌──────────────┐     ┌─────────────────┐     ┌───────────────────┐
│ WooCommerce  │────>│ StagingOrder    │────>│ ISU hook_invoke   │
│              │     │ StagingCustomer │     │ 'stageEntity'     │
│              │     │ StagingPayment  │     │                   │
│              │     │ StagingLineItem │     │ Same ISU pipeline │
└──────────────┘     └─────────────────┘     └───────────────────┘

┌──────────────┐     ┌─────────────────┐     ┌───────────────────┐
│ PayPal       │────>│ StagingPayment  │────>│ ISU hook_invoke   │
│              │     │ StagingRefund   │     │ 'stageEntity'     │
└──────────────┘     └─────────────────┘     └───────────────────┘

┌──────────────┐     ┌─────────────────┐     ┌───────────────────┐
│ Bank Import  │────>│ StagingPayment  │────>│ ISU hook_invoke   │
│              │     │ (coordinates    │     │ 'stagingExists'   │
│              │     │  with ISU for   │     │ + 'stageEntity'   │
│              │     │  cash flow)     │     │                   │
└──────────────┘     └─────────────────┘     └───────────────────┘
```
