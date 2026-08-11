# UAT Plan - ksf_FA_ProjectManagement

## Overview

This document defines the User Acceptance Test (UAT) cases for the Project Management module. UAT validates that the system meets business requirements and is ready for production deployment.

---

## 1. UAT Objectives

### 1.1 Goals

- Validate business workflows function correctly
- Confirm user requirements are met
- Ensure integration with FA works seamlessly
- Verify data accuracy and integrity
- Obtain sign-off for production deployment

### 1.2 Success Criteria

- All critical test cases pass
- No high-severity defects open
- User acceptance obtained
- Sign-off documented

---

## 2. UAT Scope

### 2.1 In Scope

- Project CRUD operations
- Task management with hierarchy
- Team assignment and allocation
- Dashboard and reporting
- Import functionality
- FA integrations (CRM, Employee)
- Security and permissions

### 2.2 Out of Scope

- Performance stress testing
- Security penetration testing
- Browser compatibility (covered in QA)
- Data migration from legacy systems

---

## 3. UAT User Roles

| Role | Description | Tests Executed |
|------|-------------|----------------|
| Project Manager | Manages projects and teams | PM-001 through PM-008 |
| Team Member | Works on assigned tasks | TM-001 through TM-003 |
| Administrator | System configuration | AD-001 through AD-003 |

---

## 4. UAT Test Cases

### 4.1 Project Management (PM)

#### UAT-PM-001: Create New Project

| Field | Value |
|-------|-------|
| Test Case ID | UAT-PM-001 |
| Scenario | Create a new project as Project Manager |
| Preconditions | User has PM_MANAGE_PROJECT permission |
| Test Steps | 1. Login as Project Manager |
| | 2. Navigate to Projects |
| | 3. Click "New Project" |
| | 4. Enter: Project ID = "TEST-001", Name = "Test Project", Description = "UAT Test", Start Date = today |
| | 5. Select Priority = "High", Status = "Planning" |
| | 6. Click Save |
| Expected Result | Success message, project appears in list |
| Acceptance Criteria | [ ] Project saved to database |
| | [ ] Project visible in list with all fields correct |
| | [ ] Activity logged |
| Result | PASS/FAIL |
| Notes | |

#### UAT-PM-002: Edit Project Details

| Field | Value |
|-------|-------|
| Test Case ID | UAT-PM-002 |
| Scenario | Modify project details |
| Preconditions | Project exists from UAT-PM-001 |
| Test Steps | 1. Edit project TEST-001 |
| | 2. Change budget to 25000 |
| | 3. Change status to "Active" |
| | 4. Save changes |
| Expected Result | Changes saved successfully |
| Acceptance Criteria | [ ] Budget updated in database |
| | [ ] Status changed to Active |
| | [ ] Activity logged |
| Result | PASS/FAIL |
| Notes | |

#### UAT-PM-003: View Projects with Filters

| Field | Value |
|-------|-------|
| Test Case ID | UAT-PM-003 |
| Scenario | Search and filter projects |
| Preconditions | Multiple projects exist with different statuses |
| Test Steps | 1. Navigate to Projects |
| | 2. Enter search term in search box |
| | 3. Click specific status filter link |
| Expected Result | Correct projects displayed |
| Acceptance Criteria | [ ] Search returns matching projects |
| | [ ] Status filter shows correct projects |
| Result | PASS/FAIL |
| Notes | |

#### UAT-PM-004: Link Project to Customer

| Field | Value |
|-------|-------|
| Test Case ID | UAT-PM-004 |
| Scenario | Associate project with FA CRM customer |
| Preconditions | Customer exists in FA |
| Test Steps | 1. Edit project TEST-001 |
| | 2. Select customer from dropdown |
| | 3. Save |
| Expected Result | Project linked to customer |
| Acceptance Criteria | [ ] Customer selection saved |
| | [ ] Customer visible in project details |
| Result | PASS/FAIL |
| Notes | |

#### UAT-PM-005: Delete Project

| Field | Value |
|-------|-------|
| Test Case ID | UAT-PM-005 |
| Scenario | Delete a project |
| Preconditions | Test project exists |
| Test Steps | 1. Navigate to project edit |
| | 2. Click Delete |
| | 3. Confirm deletion |
| Expected Result | Project removed from system |
| Acceptance Criteria | [ ] Project not in list |
| | [ ] Related tasks deleted |
| | [ ] Related assignments deleted |
| | [ ] Activity logged |
| Result | PASS/FAIL |
| Notes | |

#### UAT-PM-006: Create Project with All Fields

| Field | Value |
|-------|-------|
| Test Case ID | UAT-PM-006 |
| Scenario | Create comprehensive project with all fields |
| Preconditions | User has permissions |
| Test Steps | 1. Create new project with all fields |
| | 2. Set: name, description, start_date, end_date, budget, customer, manager, priority, status, type |
| Expected Result | All fields saved correctly |
| Acceptance Criteria | [ ] All fields stored in database |
| | [ ] All fields display correctly |
| Result | PASS/FAIL |
| Notes | |

---

### 4.2 Task Management (TM)

#### UAT-TM-001: Create Task Under Project

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TM-001 |
| Scenario | Add task to a project |
| Preconditions | Project exists |
| Test Steps | 1. Navigate to Tasks |
| | 2. Click "New Task" |
| | 3. Select project |
| | 4. Enter task details: name, description, estimated hours |
| | 5. Assign to employee |
| | 6. Set priority and status |
| | 7. Save |
| Expected Result | Task created and associated |
| Acceptance Criteria | [ ] Task saved to database |
| | [ ] Task appears in task list |
| | [ ] Linked to correct project |
| Result | PASS/FAIL |
| Notes | |

#### UAT-TM-002: Update Task Progress

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TM-002 |
| Scenario | Update task completion percentage |
| Preconditions | Task exists |
| Test Steps | 1. Edit task |
| | 2. Update progress to 50% |
| | 3. Update actual hours |
| | 4. Save |
| Expected Result | Progress updated |
| Acceptance Criteria | [ ] Progress changes saved |
| | [ ] Progress bar displays correctly |
| | [ ] Activity logged |
| Result | PASS/FAIL |
| Notes | |

#### UAT-TM-003: View Tasks by Project Filter

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TM-003 |
| Scenario | Filter tasks by project |
| Preconditions | Multiple projects with tasks exist |
| Test Steps | 1. Navigate to Tasks |
| | 2. Select specific project from dropdown |
| Expected Result | Only tasks for selected project shown |
| Acceptance Criteria | [ ] Correct filtering |
| | [ ] Project name shown for reference |
| Result | PASS/FAIL |
| Notes | |

#### UAT-TM-004: Task Overdue Detection

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TM-004 |
| Scenario | Verify overdue tasks highlighted |
| Preconditions | Task with past end_date, incomplete status |
| Test Steps | 1. Navigate to Tasks page |
| | 2. Locate overdue task |
| Expected Result | Task highlighted in red |
| Acceptance Criteria | [ ] Visual indication present |
| | [ ] Tasks past due date with non-completed status |
| Result | PASS/FAIL |
| Notes | |

#### UAT-TM-005: Create Hierarchical Tasks

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TM-005 |
| Scenario | Create parent and child tasks |
| Preconditions | Project exists |
| Test Steps | 1. Create parent task |
| | 2. Create child task with parent_task_id |
| Expected Result | Parent-child relationship established |
| Acceptance Criteria | [ ] Child task links to parent |
| | [ ] Hierarchy queryable |
| Result | PASS/FAIL |
| Notes | |

---

### 4.3 Team Management (TMG)

#### UAT-TMG-001: Assign Employee to Project

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TMG-001 |
| Scenario | Add employee to project team |
| Preconditions | Project exists, employee exists in FA |
| Test Steps | 1. Navigate to Team page |
| | 2. Select project |
| | 3. Click "Add Team Member" |
| | 4. Select employee |
| | 5. Set role = "Developer", allocation = 50% |
| | 6. Save |
| Expected Result | Employee assigned to project |
| Acceptance Criteria | [ ] Employee in team list |
| | [ ] Role displayed |
| | [ ] Allocation shown |
| Result | PASS/FAIL |
| Notes | |

#### UAT-TMG-002: View Project Team Members

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TMG-002 |
| Scenario | View team for a project |
| Preconditions | Project has team members |
| Test Steps | 1. Navigate to Team page |
| | 2. Select project |
| Expected Result | Team members displayed with details |
| Acceptance Criteria | [ ] Name, email, job title shown |
| | [ ] Role and allocation visible |
| Result | PASS/FAIL |
| Notes | |

#### UAT-TMG-003: Remove Employee from Project

| Field | Value |
|-------|-------|
| Test Case ID | UAT-TMG-003 |
| Scenario | Remove team member |
| Preconditions | Employee assigned to project |
| Test Steps | 1. Navigate to Team page |
| | 2. Select project |
| | 3. Remove employee |
| Expected Result | Employee removed from team |
| Acceptance Criteria | [ ] Employee not in list |
| | [ ] Activity logged |
| Result | PASS/FAIL |
| Notes | |

---

### 4.4 Dashboard (DB)

#### UAT-DB-001: View Dashboard Statistics

| Field | Value |
|-------|-------|
| Test Case ID | UAT-DB-001 |
| Scenario | Verify dashboard displays correct counts |
| Preconditions | Test data created in previous tests |
| Test Steps | 1. Navigate to Dashboard |
| | 2. View statistics |
| Expected Result | Dashboard shows counts |
| Acceptance Criteria | [ ] Total Projects count matches |
| | [ ] Active Projects count matches |
| | [ ] Pending Tasks count matches |
| | [ ] Overdue Tasks count matches |
| Result | PASS/FAIL |
| Notes | |

#### UAT-DB-002: View Recent Activities

| Field | Value |
|-------|-------|
| Test Case ID | UAT-DB-002 |
| Scenario | Verify activity log displays |
| Preconditions | Activities performed |
| Test Steps | 1. Navigate to Dashboard |
| | 2. View Recent Activities section |
| Expected Result | Activities listed |
| Acceptance Criteria | [ ] Activities chronologically ordered |
| | [ ] Action and details shown |
| Result | PASS/FAIL |
| Notes | |

---

### 4.5 Reports (RP)

#### UAT-RP-001: Generate Project Summary Report

| Field | Value |
|-------|-------|
| Test Case ID | UAT-RP-001 |
| Scenario | View project status summary |
| Preconditions | Projects exist |
| Test Steps | 1. Navigate to Reports |
| | 2. Select Project Summary |
| Expected Result | Report displays with counts |
| Acceptance Criteria | [ ] Status breakdown table |
| | [ ] Counts accurate |
| Result | PASS/FAIL |
| Notes | |

---

### 4.6 Import/Export (IE)

#### UAT-IE-001: Import Projects from CSV

| Field | Value |
|-------|-------|
| Test Case ID | UAT-IE-001 |
| Scenario | Bulk import projects |
| Preconditions | Valid CSV file ready |
| Test Steps | 1. Navigate to Import |
| | 2. Upload CSV file |
| | 3. Execute import |
| Expected Result | Projects created |
| Acceptance Criteria | [ ] Success message |
| | [ ] Projects in list |
| | [ ] Data matches CSV |
| Result | PASS/FAIL |
| Notes | |

#### UAT-IE-002: Update Projects via Import

| Field | Value |
|-------|-------|
| Test Case ID | UAT-IE-002 |
| Scenario | Update existing projects |
| Preconditions | Projects exist, matching IDs in CSV |
| Test Steps | 1. Prepare CSV with existing IDs and new values |
| | 2. Upload and import |
| Expected Result | Projects updated |
| Acceptance Criteria | [ ] Existing projects updated |
| | [ ] Data matches CSV |
| Result | PASS/FAIL |
| Notes | |

---

### 4.7 Security (SC)

#### UAT-SC-001: Permission - View Projects

| Field | Value |
|-------|-------|
| Test Case ID | UAT-SC-001 |
| Scenario | Access denied without permission |
| Preconditions | User without PM_VIEW_PROJECT |
| Test Steps | 1. User attempts to access Projects page |
| Expected Result | Access denied message |
| Acceptance Criteria | [ ] Error message shown |
| | [ ] No data displayed |
| Result | PASS/FAIL |
| Notes | |

#### UAT-SC-002: Permission - Manage Projects

| Field | Value |
|-------|-------|
| Test Case ID | UAT-SC-002 |
| Scenario | Create denied without permission |
| Preconditions | User without PM_MANAGE_PROJECT |
| Test Steps | 1. User attempts to create project |
| Expected Result | Access denied message |
| Acceptance Criteria | [ ] Error message shown |
| | [ ] Project not created |
| Result | PASS/FAIL |
| Notes | |

---

### 4.8 Integration (INT)

#### UAT-INT-001: Customer Dropdown Populated

| Field | Value |
|-------|-------|
| Test Case ID | UAT-INT-001 |
| Scenario | Verify FA CRM customers in dropdown |
| Preconditions | Customers exist in FA |
| Test Steps | 1. Navigate to project create/edit |
| | 2. View customer dropdown |
| Expected Result | Customers from debtors_master displayed |
| Acceptance Criteria | [ ] Customer names shown |
| | [ ] Can select customer |
| Result | PASS/FAIL |
| Notes | |

#### UAT-INT-002: Employee Data Integration

| Field | Value |
|-------|-------|
| Test Case ID | UAT-INT-002 |
| Scenario | Verify employee data from FA |
| Preconditions | Employees exist |
| Test Steps | 1. View team page |
| | 2. Check employee details |
| Expected Result | Employee data displayed |
| Acceptance Criteria | [ ] Name shown |
| | [ ] Email shown |
| | [ ] Job title shown |
| Result | PASS/FAIL |
| Notes | |

---

## 5. UAT Execution

### 5.1 Execution Checklist

- [ ] All test cases reviewed
- [ ] Test environment ready
- [ ] Test data loaded
- [ ] Test users configured
- [ ] Test cases executed
- [ ] Results documented
- [ ] Defects logged

### 5.2 Sign-off

| Role | Name | Date | Signature |
|------|------|------|----------|
| Project Manager | | | |
| QA Lead | | | |
| Development Lead | | | |

---

## 6. Test Results Summary

### 6.1 Results Summary

| Category | Total | Passed | Failed | Pass Rate |
|----------|-------|--------|--------|----------|
| Project Management | 6 | | | |
| Task Management | 5 | | | |
| Team Management | 3 | | | |
| Dashboard | 2 | | | |
| Reports | 1 | | | |
| Import/Export | 2 | | | |
| Security | 2 | | | |
| Integration | 2 | | | |
| **TOTAL** | **23** | | | |

### 6.2 Defects Found

| Defect ID | Test Case | Severity | Description | Status |
|-----------|----------|----------|-------------|--------|
| | | | | |

---

## 7. UAT Completion

### 7.1 Completion Criteria

- [ ] All critical test cases pass
- [ ] No high-severity defects open
- [ ] All test data cleaned up
- [ ] Sign-off obtained

### 7.2 Final Sign-off

This module is approved for production deployment.

| Role | Name | Date | Signature |
|------|------|------|----------|
| Business Owner | | | |
| Project Manager | | | |
| QA Lead | | | |

---
