# RTM-PROJECT-002 - Project Templates Traceability

## Requirements Traceability

**Module**: ProjectManagement
**BR**: BR-PROJECT-002

---

## Traceability Matrix

| Requirement ID | Description | FR | UT | UAT | Status |
|----------------|-------------|-----|----|-----|--------|
| **BR-PROJECT-002** | Project Templates & Activity Codes | | | | |
| REQ-001 | Template library | FR-PROJECT-002-001 | | | |
| REQ-001-AC-001 | Create template | | UT-PROJECT-002-001-001 | UAT-PROJECT-002-TC01 | |
| REQ-001-AC-002 | Add stages | | | UAT-PROJECT-002-TC01 | |
| REQ-001-AC-003 | Add activities | | | UAT-PROJECT-002-TC01 | |
| REQ-001-AC-004 | Clone template | | | UAT-PROJECT-002-TC05 | |
| REQ-002 | Apply template to project | FR-PROJECT-002-002 | | | |
| REQ-002-AC-001 | Create project from template | | UT-PROJECT-002-002-001 | UAT-PROJECT-002-TC02 | |
| REQ-002-AC-002 | Copy stages | | | UAT-PROJECT-002-TC02 | |
| REQ-002-AC-003 | Copy activities | | | UAT-PROJECT-002-TC02 | |
| REQ-002-AC-004 | Apply stage constraints | | UT-PROJECT-002-002-002 | UAT-PROJECT-002-TC03 | |
| REQ-003 | Activity validation | | | | |
| REQ-003-AC-001 | Stage active check | | | UAT-PROJECT-002-TC03 | |
| REQ-003-AC-002 | Date range validation | | | UAT-PROJECT-002-TC03 | |
| REQ-004 | Hook integration | | | | |
| REQ-004-AC-001 | project_template_applied | | | UAT-PROJECT-002-TC04 | |
| REQ-004-AC-002 | project_activity_validate | | | UAT-PROJECT-002-TC03 | |

---

## Hook Coverage

| Hook | Emitted By | Consumed By | Verified |
|------|-----------|-------------|----------|
| project_template_applied | ProjectManagement | Timesheets, TravelExpense | UAT-PROJECT-002-TC04 |
| project_activity_validate | - | Timesheets | UAT-PROJECT-002-TC03 |
| project_get_current_stage | - | TravelExpense | UAT-EXPENSE-001-TC01 |
| project_stage_get_activities | - | Timesheets, TravelExpense | UAT-PROJECT-002-TC03 |

---

*Last Updated: 2026-09-07*
