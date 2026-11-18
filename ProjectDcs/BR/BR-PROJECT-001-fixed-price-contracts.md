# BR-PROJECT-001 - Fixed Price Contracts

## Business Requirement

**Source**: webERP `contracts`, `contractbom`, `contractcharges` tables
**Module**: ksf_FA_ProjectManagement
**Status**: Proposed

### Problem Statement

Project-based businesses (consulting, construction, custom manufacturing) need fixed-price
contracts with milestone billing. FA's current PO/invoice system doesn't support:
- Contract-level billing milestones
- Progress tracking against contract value
- Contract BOM (for custom builds)
- Change order handling

### Business Value

- **Billing Accuracy**: Bill only when milestones achieved
- **Project Visibility**: Track progress vs contract value
- **Change Control**: Handle scope changes via change orders
- **Profitability**: Real-time margin tracking per contract

### Scope

#### In Scope
1. Contract header (customer, description, total value, dates)
2. Milestone definitions (description, % complete, payment amount)
3. Progress tracking (actual vs planned)
4. Contract BOM (materials/services allocated to contract)
5. Change orders (approved scope changes)
6. Billing integration (generate invoice from milestone)
7. Cost tracking (actual cost vs contract value)

#### Out of Scope
1. Time and materials contracts (use timesheets + expenses)
2. Resource scheduling (use MRP/planning module)
3. Contract templates

### Constraints

- PHP 7.3+ compatibility
- Must work with existing FA customer/invoice modules

### Dependencies

- ksf_FA_ProjectManagement
- ksf_FA_InvoiceAllocation
- ksf_FA_Timesheets (for labor cost tracking)

### Related Requirements

- FR-PROJECT-001-001: Contract creation
- FR-PROJECT-001-002: Milestone management
- FR-PROJECT-001-003: Progress tracking
- FR-PROJECT-001-004: Change orders
- FR-PROJECT-001-005: Contract billing
- FR-PROJECT-001-006: Cost tracking
