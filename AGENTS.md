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

### Core Principles
- **SOLID**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion
- **DRY**: Don't Repeat Yourself - extract reusable logic
- **TDD**: Test-Driven Development - write tests first
- **DI**: Dependency Injection - inject dependencies, don't hardcode
- **SRP**: Single Responsibility Principle - each class has one reason to change
- **Polymorphism over Conditionals**: Use SRP classes and polymorphism instead of conditional logic. **Minimize if/then/else and switch statements**

## Repository Structure

```
ksf_FA_ImportStagingProcessing/
├── src/                    # Business logic (framework-agnostic)
│   ├── Contracts/        # Interfaces (StagingManagerInterface, ProcessorInterface, etc.)
│   ├── Services/         # Staging services, processing pipeline
│   ├── Models/           # StagingCustomer, StagingTransaction, etc.
│   ├── Exceptions/      # Custom exceptions
│   └── Validators/      # Input validation
├── pages/                  # FA UI pages
├── sql/                    # Database schemas (staging tables)
├── tests/
│   ├── Unit/             # PHPUnit tests
│   └── Integration/     # Integration tests
├── ProjectDocs/            # Project documentation
│   ├── Requirements.md
│   ├── RTM.md            # Requirements Traceability Matrix
│   ├── BABOK.md         # Business Analysis Body of Knowledge
│   └── UML.md           # UML diagrams
├── hooks.php               # FA hooks integration
├── ksf_import_staging.php  # Module entry point
├── composer.json
├── phpunit.xml
└── README.md
```

## Coding Standards

### PHP Compatibility
- **Target**: PHP 7.3+ (with eye to PHP 8.x upgrades)
- Use `declare(strict_types=1)` at top of all PHP files
- Avoid PHP 8+ features until we drop PHP 7.3 support

### Naming Conventions
- **Interfaces**: `InterfaceNameInterface` (e.g., `StagingManagerInterface`)
- **Abstract classes**: `AbstractClassName` (e.g., `AbstractStagingProcessor`)
- **Services**: `ServiceNameService` (e.g., `StagingService`)
- **Models/DTOs**: `ModelName` (e.g., `StagingCustomer`, `StagingTransaction`)
- **Exceptions**: `PascalCase` ending with `Exception`
- **PSR-4 Autoloading**: Standard PHP autoloading
- **Namespace Organization**: Logical grouping by functionality

### Documentation
Every class/method MUST have:
```php
/**
 * Short description
 *
 * Long description with business context
 *
 * @UML Note: Class diagram in ProjectDocs/UML.md
 * @BABOK Related: Requirements analysis, Solution evaluation
 * @requirement REQ-001
 */
```

## Testing Strategy

### TDD Red-Green-Refactor
1. **RED**: Write failing test
2. **GREEN**: Write minimal code to pass
3. **REFACTOR**: Improve code while keeping tests green

### Requirement Mapping
- All unit tests must map back to specific requirements in the traceability matrix
- Each code unit should indicate which requirement it fulfills

### Test Structure
```php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class StagingTransactionTest extends TestCase
{
    public function testCanStageTransaction(): void
    {
        // Arrange
        $transaction = new StagingTransaction();
        
        // Act
        $transaction->setSource('woocommerce');
        
        // Assert
        $this->assertEquals('woocommerce', $transaction->getSource());
    }
}
```

### Coverage Target
- **100% coverage** for all classes and methods
- Test all boundary conditions, error scenarios, and invalid inputs
- Use mocks/stubs for external dependencies
- PHPUnit for unit testing

## Design Patterns

### Strategy Pattern
- Processing pipelines for different source types (WooCommerce, Square, PayPal)
- Allows swapping algorithms at runtime

### Factory Pattern
- Service creation, complex object creation

### Repository Pattern
- Data access abstraction (DB-agnostic, via ksf_ModulesDAO)

### Observer Pattern
- Event-driven architecture for staging lifecycle (staged, mapped, processed, reconciled)

### DTO Pattern
- Form/request data encapsulation (see DTO section in ksf_FA_Square AGENTS.md)

## Database Design

### Staging Tables
- `staging_customers` - Unified customer staging from all sources
- `staging_transactions` - Unified transaction staging from all sources
- `staging_mapping` - Field mapping configuration per source
- `staging_log` - Processing audit trail

### Design Principles
- Normalized schema for data integrity
- Performance-optimized indexing
- Versioned database migrations
- Audit trails and change tracking

## Layer Architecture

### Presentation Layer (pages/)
- FA UI pages for staging management
- Processing workflow screens
- Reconciliation and error resolution interfaces
- HTML generated as strings via HtmlElement library (no direct echo)
- Reusable UI components (tables, forms, buttons)

### Business Logic Layer (src/)
- Staging services (ingest from source modules)
- Processing pipeline (transform stage → FA entities)
- Validation and mapping logic
- Exception handling with custom exception hierarchy
- **Requirement references**: ALL code must reference the requirement it fulfills in PHPDoc

### Data Access Layer
- DAO classes via ksf_ModulesDAO
- Standardized query patterns
- Transaction management for data consistency

### Infrastructure Layer
- Logging with configurable levels (error, warning, info, debug)
- File handling via ksf_file
- External service integration points

## Inter-Module Communication

All ksf modules should implement 4 standard methods in their hooks class:
1. `getModuleConstants(&$data, $opts)` - Returns module constants
2. `getModuleCapabilities(&$data, $opts)` - Returns capabilities with descriptions
3. `hasCapability(&$data, $opts)` - Checks for specific capability
4. `respondToCapabilityRequest(&$data, $opts)` - Generic responder

```php
// Get staging data from ksf_FA_Square
$data = [];
$stagedTransactions = hook_invoke('ksf_FA_Square', 'getStagedTransactions', $data);

// Check if woocommerce module has import capability
$data2 = [];
$hasImport = hook_invoke('ksf_generate', 'hasCapability', $data2, ['capability' => 'woocommerce_import']);
```

See `AGENTS_MODULE_COMMUNICATION_ADDENDUM.md` for full documentation.

## Version Tagging

Follow Semantic Versioning (SemVer): `MAJOR.MINOR.PATCH`
- **MAJOR**: Incompatible API changes
- **MINOR**: New functionality (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

```bash
git tag -a v1.0.0 -m "Initial release with staging and processing pipeline"
git push origin v1.0.0
```

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

## Quality Assurance

### Code Review Checklist
- SOLID principles compliance
- PHPDoc completeness with requirement references
- Test coverage verification (100%)
- Security considerations (input validation, SQL injection prevention, XSS protection, CSRF)
- Performance implications
- Design pattern usage

### Continuous Integration
- Automated testing on commits
- Code quality checks (PHPStan, PHPMD)
- Dependency vulnerability scanning
- Documentation generation

## Security Requirements
- Input validation and sanitization
- SQL injection prevention (parameterized queries)
- XSS protection in HTML output
- CSRF protection for forms
- Access control integration
- Audit logging for all operations
- Secure configuration management

## Performance Requirements
- Efficient database queries with proper indexing
- Memory-efficient processing of large datasets (pagination/chunking)
- Transaction management for data consistency
- Caching strategies for repeated operations
- Lazy loading for large object graphs
- Query optimization and N+1 problem prevention

## Environment Management
- **Development Environment**: Local development setup
- **Staging Environment**: Pre-production testing
- **Production Environment**: Live system configuration
- **Environment Parity**: Consistent environments across stages

## .gitignore

```
/vendor/
/composer.lock
.phpunit.cache/
.idea/
.vscode/
*.swp
```
