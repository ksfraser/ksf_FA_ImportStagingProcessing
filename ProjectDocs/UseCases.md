# Use Cases — ksf_FA_ImportStagingProcessing

## UC-01: Stage Customer

| Element | Value |
|---------|-------|
| **ID** | UC-01 |
| **Name** | Stage Customer |
| **Trigger** | Source module (WooCommerce/Square) provides customer data |
| **Primary Actor** | Source Module |
| **Preconditions** | Source module has extracted customer data from external system |
| **Postconditions** | Customer is inserted into `staging_customers` with status 'staged' |

### Basic Flow
1. Source module calls `hook_invoke('ksf_FA_ImportStagingProcessing', 'stageCustomer', $data)` or uses `StagingCustomerDAO` directly
2. System validates customer data (required fields, format)
3. System creates `StagingCustomer` DTO
4. System inserts record into `staging_customers` table
5. System logs action in `staging_log`
6. System emits 'staged' event
7. System returns the staged record with ID

### Alternative Flows
- **Invalid Data**: System returns validation errors, logs failure
- **Duplicate**: System detects existing source_customer_id, returns existing record

---

## UC-02: Stage Transaction

| Element | Value |
|---------|-------|
| **ID** | UC-02 |
| **Name** | Stage Transaction |
| **Trigger** | Source module provides transaction data |
| **Primary Actor** | Source Module (WooCommerce/Square/PayPal/Bank) |
| **Preconditions** | Source module has extracted transaction from external system |
| **Postconditions** | Transaction is inserted into `staging_transactions` with status 'staged' |

### Basic Flow
1. Source module calls `stageTransaction` via hooks or DAO
2. System validates transaction data (amount, date, source)
3. System creates `StagingTransaction` DTO
4. System inserts record into `staging_transactions` table
5. System logs action in `staging_log`
6. System emits 'staged' event

### Alternative Flows
- **Duplicate Source ID**: System detects duplicate, logs warning, returns existing
- **Missing Required Fields**: System returns validation errors

---

## UC-03: Audit Trail

| Element | Value |
|---------|-------|
| **ID** | UC-03 |
| **Name** | Audit Trail |
| **Trigger** | Any staging or processing operation |
| **Primary Actor** | System (automatic) |

### Basic Flow
1. Any operation (staged, validated, matched, processed, failed) triggers logging
2. System creates log entry with record_type, record_id, action, source, details
3. Log entry is persisted in `staging_log`

---

## UC-04: Match and Process Transactions

| Element | Value |
|---------|-------|
| **ID** | UC-04 |
| **Name** | Match and Process Transactions |
| **Trigger** | User clicks "Process Queue" or scheduled task |
| **Primary Actor** | FA Administrator / System |
| **Preconditions** | Staged transactions exist with status 'staged' or 'validated' |
| **Postconditions** | Matched transactions processed; unmatched flagged for review |

### Basic Flow
1. System retrieves staged transactions with status 'staged' or 'validated'
2. For each transaction, system finds potential FA matches by amount, date, customer
3. Scoring-based matching calculates confidence
4. Confidence >= 95%: auto-approve, process into FA
5. Confidence 80-95%: flag for manual review
6. Confidence < 80%: mark as unmatched
7. Processed results logged in `staging_log`

### Alternative Flows
- **Manual Review**: FA Administrator reviews flagged matches, approves or rejects
- **Processing Error**: System catches errors, marks record as 'failed', logs details

---

## UC-05: Configure Mappings

| Element | Value |
|---------|-------|
| **ID** | UC-05 |
| **Name** | Configure Field Mappings |
| **Trigger** | Administrator opens mapping configuration |
| **Primary Actor** | FA Administrator |
| **Preconditions** | Administrator has appropriate permissions |
| **Postconditions** | Mapping configuration saved |

### Basic Flow
1. Administrator selects source type
2. System displays current field mappings
3. Administrator configures source-to-target field mapping
4. Administrator saves configuration
5. System persists to `staging_mapping` table

---

## UC-06: Upgrade Staging Schema

| Element | Value |
|---------|-------|
| **ID** | UC-06 |
| **Name** | Upgrade Staging Schema |
| **Trigger** | Module installation or update |
| **Primary Actor** | System (automatic) |
| **Preconditions** | Module is being installed or updated |
| **Postconditions** | Staging tables exist with current schema; existing production data preserved |

### Basic Flow
1. System checks if staging tables exist
2. If not, creates them with full schema
3. If yes, runs ALTER TABLE for any missing columns
4. Existing data is preserved (never DROP or TRUNCATE)
5. Migration is logged
