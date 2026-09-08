# ProjectModule vs dotProject vs OpenProject - Capabilities Comparison

## Overview

This comparison addresses: "How does the PM module's requirements compare to what dotProject or OpenProject provides in terms of capabilities?"

## Repository Analysis

| Feature | dotProject (2005-2013) | OpenProject (Active, Ruby) | Our PM (BR-PROJECT-002 + ARCH) |
|---------|------------------------|---------------------------|--------------------------------|
| **Architecture** | Procedural PHP 4/5.3 | Ruby on Rails (MVC) | PHP 7.3 + Hook Architecture |
| **Service Layer** | None | Service objects | Yes (Hook-based DI) |
| **Template System** | None | Work packages + templates | Project → Stage → Activity templates |
| **Industry Verticals** | Basic | Custom fields | Pre-seeded: Software, Construction, Sales, Marketing, Generic |
| **Multi-stage Projects** | Tasks only | Work packages (nested) | Explicit Stage → Activity hierarchy |
| **Activity Constraints** | None | Custom fields | Active flag + Date range constraints |
| **Contract Integration** | None (separate module) | Budget/Contracts module | Contract billing rules (cost, cost_plus, fixed_rate) |
| **Billing Rules** | None | Budget tracking only | Per-activity billing rules applied at approval |
| **Expense Tracking** | Basic (Expenses module) | Costs module | Full integration (hook-based) |
| **Time Tracking** | Timecards (basic) | Time tracking (integrated) | Timesheets + Activity codes |
| **Approval Chain** | Basic approval | Workflow engine (configurable) | Multi-level with delegation, escalation, timeout |
| **Cross-module Integration** | Direct DB queries | REST API + direct | Hook-based (`hook_invoke_all` contracts) |
| **Status Workflow** | Basic (pending/accepted/rejected) | Complex workflow engine | Full workflow: Draft → Submitted → Approved/Rejected/Denied → Returned |
| **Audit Trail** | Basic log | Full history | `approval_steps` + `comments` + xref tracking |
| **Auto-approval** | None | Not built-in | Small expenses (< $X), meals (< $Y), timesheets within range |
| **Multi-currency** | None | Limited | Full multi-currency with conversion |
| **Resource Scheduling** | Gantt (basic) | Gantt + Scheduling | Not included (use MRP) |
| **Mobile Access** | Web only | Web + mobile responsive | Web (future: mobile via hooks) |
| **GPG Integration** | None | None | Yes (key management via hooks) |
| **GDPR Tools** | None | Audit + GDPR tools | Yes (separate module) |
| **E-Invoicing** | None | Basic invoicing | Full PDP integration (French standard) |

## Key Advantages of Our Design

**Hook-based Integration**: Unlike dotProject (direct SQL queries) and OpenProject (REST API + internal service calls), our architecture uses `hook_invoke_all` contracts, allowing modules to communicate without direct dependencies.

**Industry Template Library**: Pre-configured templates for Software Development, House Construction, Sales, Marketing, Manufacturing, and Generic business - none of which exist in dotProject or OpenProject as first-class features.

**Activity Constraint System**: Stage-level constraints (active flag, date ranges) with hook-based validation (`project_stage_access_check`) provide granular control over what activities are available at any point in a project.

**Billing Rule Integration**: Per-activity billing rules applied at approval time (not entry time) through the `expense_get_billing_rule` hook, enabling contract-level customization.

**Expense-Time Correlation**: The `expense_check_time_correlation` and `time_check_expense_correlation` hooks enable tracking of related time/expense entries within +/- 1 day ranges.

**Direct Delivery System**: Expenses create `billing_batch_items` (consumed, not inventory) that feed into AR batch processing for invoicing, avoiding `stock_master` pollution.

---

## OrangeHRM Timesheets Comparison

**Source**: OrangeHRM (PHP 7.x, procedural/OO hybrid, active)

### OrangeHRM Timesheet Capabilities

| Feature | OrangeHRM | Our Design (BR-TIME-001) |
|---------|-----------|------------------------|
| Basic time tracking | Yes | Yes |
| Project/task association | Yes (Project module) | Yes (ProjectManagement) |
| Activity codes | Basic categories | Multi-layer (Project → Stage → Activity) |
| Approval chain | Basic manager approval | Multi-level with delegation, escalation, timeout |
| Integration with expenses | No direct integration | Hook-based (`expense_check_time_correlation`) |
| Payroll export | Payroll module (separate) | `timesheet_export_payroll` hook → Payroll module |
| Overtime calculation | Basic (configured per country) | Moved to Payroll (not hardcoded) |
| Status tracking | Basic (Pending, Approved, Rejected) | Full: Draft → Submitted → Approved/Denied/Rejected → Returned |
| Status change comments | Limited | `approval_step_comments` xref table |
| Multi-currency | Limited | Full |
| Mobile | Mobile web | Not included (future) |

### Key Differentiator

Our design separates **time tracking** (pure: project/activity + time by day) from **payroll** (OT calculation, rates, country-specific rules) through hook contracts. OrangeHRM combines these, making customization harder.

The `timesheet_approved` hook emits data for **both** billing (`contract_get_billing_rule`) and payroll (`timesheet_export_payroll`), enabling independent processing paths.
