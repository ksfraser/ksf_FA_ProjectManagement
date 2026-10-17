# ARCH-PROJECT-CAPABILITIES-INVENTORY — dotProject/SuiteCRM/Odoo baseline vs ksf_FA_ProjectManagement

**Status:** Proposed
**Purpose:** Capability gap analysis against the three reference PM tools the
user cited, feeding BR-PROJECT-001..005 and the FR set.

## Legend

- `✅` shipped in current PM module
- `▣` architected, doc'd (BR/FR/ARCH/UT) — pending code/tests (this phase)
- `◻` out of scope / by-design omitted

## Matrix

| Capability | dotProject | SuiteCRM | Odoo PM | ksf_FA_PM |
|---|---|---|---|---|
| Projects CRUD + types | ✅ | ✅ | ✅ | ✅ |
| Tasks w/ hierarchy (parent) | ✅ | ✅ | ✅ | ✅ |
| Assignments + allocation % | ✅ | ✅ | ✅ | ✅ |
| Time tracking (est/actual) | ✅ | ✅ | ✅ | ✅ |
| Progress % + activity log | partial | partial | ✅ | ✅ |
| **Milestones (is_milestone, target date)** | ✅ | ✅ | ✅ | ▣ BR-003/003-001 |
| **Task dependencies (FS/SS/FF/SF + lag)** | ✅ | ◻ | ✅ | ▣ BR-003/003-002 |
| **CPM schedule (ES/EF/LS/LF/slack/critical)** | ◻ (PERT-lite only) | ◻ | auto-scheduling opt | ▣ BR-003/003-001, ARCH-003 |
| **Scheduling constraints (SNET/SNLT/Must Start…)** | ◻ | ◻ | ✅ (OpenProject-style) | ▣ BR-003/003-001 |
| **Cycle guard (no DAG loops)** | ◻ (hangs) | ◻ | ✅ | ▣ FR-003-002 |
| Project templates | ✅ | ✅ | ✅ | ✅ BR-002 |
| **Documents / file attachments** | ✅ | ✅ (WebDAV/Notes in SuiteCRM) | ✅ | ▣ BR-004 (FA-native reuse, diff save loc) |
| Customer (debtor) integration | ◻ | ✅ (Accounts) | ✅ | ✅ BR-001 |
| **Calendar feed → FA Calendar** | ◻ | ✅ (Calendar module) | ✅ | ▣ BR-005 / FR-005-001 (emitter) |
| **Gantt visualization** | ✅ (dotProject is Gantt-first) | ◻ | ✅ | ▣ BR-005 / FR-005-002 |
| CSV import | ◻ | ✅ | ✅ | ✅ |
| Revenue/orders tracking | ◻ | ✅ | ✅ | ✅ BR-001 |

## Findings

1. **dotProject** is Gantt-first; its CPM is PERT-estimate PERT-lite, not
   full CPM — PM's CPM engine (ARCH-003) exceeds it.
2. **SuiteCRM** has no task-dependency/CPM; its Calendar module exists and
   PM emits against the same protocol → parity on the calendar axis.
3. **Odoo** auto-scheduling is a licensed add-on; PM implements the same
   forward/backward CPM on the engine directly, no license.
4. **The one genuinely new + differentiating capability = CPM engine with
   lifecycle-workflow hooks + cycle guard** (dotProject hangs on loops).
