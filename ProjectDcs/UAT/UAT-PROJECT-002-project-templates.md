# UAT-PROJECT-002 - Project Templates UAT Plan

## User Acceptance Testing

**Module**: ProjectManagement
**BR**: BR-PROJECT-002
**Tester**: Project Manager / System Admin

---

## Test Environment

- FA Instance: http://192.168.1.102:8090
- Modules: ksf_FA_ProjectManagement, ksf_FA_Timesheets, ksf_FA_TravelExpense

---

## Test Scenarios

### UAT-PROJECT-002-TC01: Create Software Development Template

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigate to Project Templates | Template list displays |
| 2 | Click "Add Template" | Template form displays |
| 3 | Enter: Code="SD-TEMPLATE", Name="Software Development", Industry="Software" | Template saved |
| 4 | Add Stage "Requirements" | Stage added with sequence 1 |
| 5 | Add Activities: "BR Writing", "Stakeholder Interviews", "Requirements Doc" | Activities added |
| 6 | Add Stage "Development" with date constraint (active_from, active_to) | Stage added with date range |
| 7 | Verify template appears in list | Template visible |

**Pass Criteria**: Template created with stages and activities

---

### UAT-PROJECT-002-TC02: Create Project from Template

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Navigate to Projects | Project list displays |
| 2 | Click "New Project" | Project form displays |
| 3 | Select template "Software Development" | Template details loaded |
| 4 | Enter: Project Code="PRJ-001", Name="CRM Module", Customer="Acme" | Project created |
| 5 | Verify stages copied from template | Stages visible in project |
| 6 | Verify activities copied for each stage | Activities visible |
| 7 | Verify stage date constraints inherited | Constraints applied |

**Pass Criteria**: Project created with all stages and activities from template

---

### UAT-PROJECT-002-TC03: Stage Activity Constraint

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Open project "PRJ-001" | Project details display |
| 2 | Go to Time Entry | - |
| 3 | Try to add time to "Development" activity | Allowed (stage active) |
| 4 | Try to add time to "Testing" activity | Rejected (Testing stage not active) |
| 5 | Verify error message | "Stage not active" message |

**Pass Criteria**: Activities constrained by stage active status

---

### UAT-PROJECT-002-TC04: Hook Emission on Template Apply

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Create project from template | Project created |
| 2 | Check audit log | project_template_applied hook logged |
| 3 | Check timesheet module | Activities available for project |
| 4 | Check expense module | Activities available for project |

**Pass Criteria**: Hook emits and other modules receive project data

---

### UAT-PROJECT-002-TC05: Clone Template

| Step | Action | Expected Result |
|------|--------|-----------------|
| 1 | Open "Software Development" template | Template details display |
| 2 | Click "Clone as New Version" | New version created (v2) |
| 3 | Modify stage sequence | Changes saved |
| 4 | Verify original template unchanged | v1 unchanged |

**Pass Criteria**: Template cloned with version increment

---

## Sign-Off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Project Manager | | | |
| System Admin | | | |
| QA | | | |
