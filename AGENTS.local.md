<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

# AGENTS.md - ksf_FA_ImportStagingProcessing
> Unified staging tables (Customers + Transactions) and processing for post-import operations from WooCommerce, Square API, Square CSV, and other third-party sources.
## Architecture Overview
This project unifies the post-import staging and processing logic that was previously scattered across three separate module stacks. Each source module (WooCommerce, Square API, Square CSV) handles its own import mechanics; this module provides the **shared staging layer** and **processing pipeline** between import and FrontAccounting.
### Module Sources
| Source | Import Mechanism | This Module's Role |
|--------|-----------------|-------------------|
| **WooCommerce** | `ksf_generate` → WooCommerce Import | Stage orders/customers, map to FA, process |
| **Square API** | `ksf_FA_Square` → Square API Connector | Stage transactions/customers, reconcile, process |
| **Square CSV** | `FA_ImportSquareUp` → Square CSV Parser | Stage CSV transactions, map fields, process |
| **PayPal** | `FA_ImportSquareUp` → PayPal Import | Stage PayPal transactions, process |
| **Bank Import** | `ksf_bank_import` | Stage bank transactions, reconcile |
## Database Design
### Staging Tables
- `staging_customers` - Unified customer staging from all sources
- `staging_transactions` - Unified transaction staging from all sources
- `staging_mapping` - Field mapping configuration per source
- `staging_log` - Processing audit trail
## Composer Configuration
```json
{
    "name": "ksfraser/import-staging-processing",
    "description": "Unified staging and processing for third-party imports into FrontAccounting",
    "type": "fa-module",
    "require": {
        "php": ">=7.3",
        "ext-json": "*",
        "ksfraser/modules-dao": "^1.0",
        "ksfraser/contact-dto": "^0.1",
        "ksfraser/file": "^1.0"
    },
    "autoload": {
        "psr-4": {
            "Ksfraser\\ImportStaging\\": "src/"
        }
    }
}
```
## Shared Library Dependencies
| Library | Purpose | Source |
|---------|---------|--------|
| `ksfraser/contact-dto` | Contact/payee data transfer objects | ksf_libs/Contact-DTO |
| `ksfraser/traits` | Reusable PHP traits | ksf_libs/Traits |
| `ksfraser/exceptions` | Centralized exception hierarchy | ksf_libs/Exceptions |
| `ksfraser/modules-dao` | Cross-platform DAO abstraction | ksf_libs/ksf_ModulesDAO |
| `ksfraser/file` | Format-aware file IO (CSV/JSON) | ksf_libs/FILE |
| `ksfraser/famock` | FA mock routines for testing | ksf_libs/FAMock |
| `ksfraser/fa-hooks` | FA hook system | GitHub: ksfraser/FA_Hooks |
## Exception Hierarchy
```
Ksfraser\ImportStaging\Exceptions\
├── StagingException (base)
│   ├── StagingNotFoundException
│   ├── InvalidSourceException
│   ├── DuplicateTransactionException
│   ├── MappingException
│   └── ProcessingException
```
All exceptions extend from `\RuntimeException` with factory methods for common instantiation patterns.
## Trait Usage
Prefer trait composition over deep inheritance:
```php
use Ksfraser\Traits\ValidatableTrait;
use Ksfraser\Traits\EventEmitterTrait;
use Ksfraser\Traits\EntityStateTrait;
use Ksfraser\Traits\TimestampTrait;
```
## RTM (Requirements Traceability Matrix)
See `ProjectDocs/RTM.md` for full traceability:
- Requirement ID → Test Case ID → Code File → Version
## BABOK Alignment
See `ProjectDocs/BABOK.md` for business analysis alignment:
- Stakeholder needs → Solution approach → Acceptance criteria
## UML Documentation
See `ProjectDocs/UML.md` for:
- Class diagrams
- Sequence diagrams
- Component diagrams
- Message flow diagrams
## Environment Management
- **Development Environment**: Local development setup
- **Staging Environment**: Pre-production testing
- **Production Environment**: Live system configuration
- **Environment Parity**: Consistent environments across stages
