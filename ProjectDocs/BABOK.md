# BABOK Alignment — ksf_FA_ImportStagingProcessing

## Overview

This document maps the ksf_FA_ImportStagingProcessing project to the **Business Analysis Body of Knowledge (BABOK 3.0)** knowledge areas, tasks, and techniques. Each requirement and design decision is traced to BABOK tasks.

---

## Knowledge Area 1: Business Analysis Planning and Monitoring

### Task: Plan Business Analysis Approach
| Element | Application |
|---------|-------------|
| Approach | Agile BA — iterative discovery with working software as primary deliverable |
| Techniques | Stakeholder List, Business Process Modeling, Scope Modeling |
| Outputs | This BABOK document, Requirements.md, RTM.md, UML.md, UseCases.md |

### Task: Plan Stakeholder Engagement
| Stakeholder | BABOK Role | Engagement Strategy |
|-------------|------------|---------------------|
| FA Administrator | Domain SME | UI reviews, processing workflow testing |
| Source Module Devs | Implementation | Interface design reviews, hook API validation |
| Finance/Accounting | Business Sponsor | Requirements sign-off, reconciliation reports |
| Developer (ksf) | Implementation | AGENTS.md conventions, peer reviews |

### Task: Plan BA Information Management
| Artifact | Repository | Access |
|----------|------------|--------|
| Requirements | ProjectDocs/Requirements.md | All team members |
| RTM | ProjectDocs/RTM.md | All team members |
| UML | ProjectDocs/UML.md | All team members |
| Code | GitHub | Developers |
| Test cases | tests/ directory | QA team |

---

## Knowledge Area 2: Elicitation and Collaboration

### Task: Prepare for Elicitation
| Source | Technique | Outcome |
|--------|-----------|---------|
| ksf_FA_Square src/ | Interface Analysis | Current staging interfaces and patterns |
| FA_ImportSquareUp (legacy) | Interface Analysis | Existing prod staging tables and matching logic |
| ksf_generate (WooCommerce) | Interface Analysis | WooCommerce staging approach |
| ksf_bank_import | Interface Analysis | Bank import staging approach |
| ksf_FA_Common | Interface Analysis | Base hooks class patterns |

### Task: Conduct Elicitation
| Session | Technique | Result |
|---------|-----------|--------|
| Repository Analysis | Document Analysis | Mapped all 3 source module staging approaches |
| Inter-Module Comm Review | Interface Analysis | Standardized 4-method hook_invoke pattern |
| Staging Design Review | Stakeholder Interview | Unified staging table design with backward compat |

---

## Knowledge Area 3: Requirements Life Cycle Management

### Task: Trace Requirements
See **RTM.md** for the full Requirements Traceability Matrix. Every functional requirement maps to a code file and test case.

### Task: Maintain Requirements
| Change Driver | Impact | Process |
|---------------|--------|---------|
| New source type added | New source constant + mapping | FR -> RTM -> Code -> Test |
| FA version upgrade | Table/API changes | Update mapping layer |
| New field requirements | Schema migration via ALTER TABLE | Extend DAO ensureTableExists() |

### Task: Prioritize Requirements
Using MoSCoW:

**Must Have**
- FR-01.01 through FR-01.07: Unified staging tables with backward compat
- FR-02.01 through FR-02.06: Processing pipeline with scoring matching
- FR-03.01 through FR-03.02: Standard hooks API
- NFR-01 through NFR-08: Quality attributes

**Should Have**
- FR-03.03 through FR-03.04: Composer package for DAO access
- FR-04.01 through FR-04.03: Event-driven architecture
- FR-05.01 through FR-05.04: Configuration UI

**Could Have**
- Advanced reconciliation dashboards
- Batch processing with progress tracking

**Won't Have (v1.0)**
- Real-time sync triggers
- Machine learning match enhancement

---

## Knowledge Area 4: Strategy Analysis

### Task: Analyze Current State
| Element | Current State |
|---------|---------------|
| Business Process | Each import module has own staging logic, tables, and processing |
| Technology | 3+ separate staging implementations, no shared layer |
| Pain Points | Duplicate code, inconsistent field handling, no unified matching |
| Production Data | Existing `0_ksf_import_square_*` tables with matched data must be preserved |

### Task: Define Future State
| Element | Future State |
|---------|--------------|
| Staging | Single unified schema shared across all import modules |
| Processing | Consistent pipeline: Stage → Validate → Match → Process → Audit |
| Integration | Standard API via hooks for staging access |
| Data Safety | Zero-loss migration with ALTER TABLE strategy |

### Task: Assess Risks
| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Data loss during schema migration | Low | Critical | ALTER TABLE only, never DROP/recreate |
| Breaking existing import flows | Medium | High | Full backward-compat testing |
| PHP version constraints | Low | Medium | Target PHP 7.3+, avoid 8+ features |

### Task: Define Change Strategy
| Phase | Scope | Timeline |
|-------|-------|----------|
| Phase 1: Foundation | ProjectDocs, schema, Contracts, Models | Current |
| Phase 2: Core Services | DAO, Services, Validators, ProcessingPipeline | Next |
| Phase 3: Integration | hooks.php, composer.json, tests | Next |
| Phase 4: Adoption | Migrate source modules to use unified staging | After v1.0 |

---

## Knowledge Area 5: Requirements Analysis and Design Definition

### Task: Specify and Model Requirements
| Artifact | Technique | Content |
|----------|-----------|---------|
| Requirements.md | FR Specification | Functional + non-functional requirements |
| RTM.md | Traceability Matrix | FR -> UC -> Code -> Test |
| UML.md | UML diagrams | Class, sequence, component diagrams |
| UseCases.md | Use Case Specification | Detailed flow for each UC |

### Task: Define Design Options
| Option | Description | Recommendation |
|--------|-------------|----------------|
| Monolithic (legacy) | Per-module staging | Rejected — violates DRY |
| Shared library (chosen) | Unified staging via Composer + hooks | **Selected** |
| FA core integration | Modify FA core for staging | Rejected — too invasive |

### Task: Recommend Solution
**Unified staging module** following AGENTS.md:
- `src/Contracts/` — Service interfaces
- `src/Models/` — DTOs
- `src/DAO/` — Data access layer
- `src/Services/` — Business logic
- `src/Validators/` — Input validation
- `src/Exceptions/` — Custom exceptions
- `hooks.php` — FA integration with 4 standard methods
- `sql/install.sql` — Unified schema with ALTER TABLE upgrades

---

## Knowledge Area 6: Solution Evaluation

### Task: Measure Solution Performance
| Metric | Target | Measurement |
|--------|--------|-------------|
| Staging success rate | >99% | Success vs failed staging operations |
| Match accuracy | >95% | Spot-check matched transactions |
| Test coverage | 100% | PHPUnit coverage reports |

### Task: Assess Solution Limitations
| Limitation | Impact | Workaround | Enhancement Plan |
|------------|--------|------------|------------------|
| PHP 7.3 target | No 8.x features | Use compatible syntax | Plan PHP upgrade |
| FA table prefix convention | Dynamic table names | Use TB_PREF constant | Standard FA pattern |

---

## BABOK Techniques Applied

| Technique | BABOK Ref | Application |
|-----------|-----------|-------------|
| Business Process Modeling | 10.4 | Import-to-processing workflow |
| Data Flow Diagrams | 10.14 | Source -> Staging -> FA data movement |
| Data Modeling | 10.15 | Unified staging tables |
| Document Analysis | 10.18 | Source module code review |
| Functional Decomposition | 10.20 | Requirements -> Use Cases -> Code |
| Interface Analysis | 10.25 | Module hook communication |
| Metrics and KPIs | 10.28 | Success rate, accuracy |
| Process Modeling | 10.35 | Stage, Validate, Match, Process flows |
| Risk Analysis | 10.39 | Data loss, compat, PHP version |
| Scope Modeling | 10.42 | In-scope / out-of-scope matrix |
| Stakeholder List | 10.44 | FA Admin, Developers, Finance |
| Traceability Matrix | 10.47 | RTM.md |
| UML Diagrams | 10.48 | UML.md |
