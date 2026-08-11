# Test Plan - ksf_FA_ProjectManagement

## Overview

This document outlines the test strategy, test types, test cases, and acceptance criteria for the Project Management module.

---

## 1. Test Strategy

### 1.1 Test Objectives

- Verify all functional requirements are met
- Ensure data integrity and consistency
- Validate integration with FA core
- Confirm security controls work correctly
- Achieve code quality standards

### 1.2 Test Levels

| Level | Description | Coverage Target |
|-------|-------------|-----------------|
| Unit Testing | Individual function/method testing | Core business logic |
| Integration Testing | Module integration with FA | All integrations |
| System Testing | End-to-end workflows | Critical paths |
| User Acceptance Testing | Business user validation | All use cases |

### 1.3 Test Types

| Type | Description |
|------|-------------|
| Functional Testing | Feature verification |
| Regression Testing | Existing functionality |
| Security Testing | Permission and access |
| Performance Testing | Response times |
| UI/UX Testing | User interface |

---

## 2. Test Environment

### 2.1 Environment Requirements

- FrontAccounting 2.4.0+ installed
- PHP 8.0+
- MySQL 5.7+
- Web browser (Chrome/Firefox/Edge)
- Sample data loaded

### 2.2 Test Data

**Required Test Data**:
- At least 5 sample projects (different statuses)
- At least 10 sample tasks (different statuses, priorities)
- At least 3 sample employees
- At least 1 sample customer (debtor)

---

## 3. Test Cases

### 3.1 Project Management Tests

#### TC-PM-001: Create New Project

| Field | Value |
|-------|-------|
| Test ID | TC-PM-001 |
| Description | Create a new project with all required fields |
| Preconditions | User has PM_MANAGE_PROJECT permission |
| Steps | 1. Navigate to Projects page |
| | 2. Click "New Project" |
| | 3. Fill required fields |
| | 4. Click Save |
| Expected Result | Project saved to database, appears in list |
| Pass Criteria | Project visible in list with correct data |

#### TC-PM-002: View Project List

| Field | Value |
|-------|-------|
| Test ID | TC-PM-002 |
| Description | View list of all projects |
| Preconditions | User has PM_VIEW_PROJECT permission |
| Steps | 1. Navigate to Projects page |
| | 2. View displayed list |
| Expected Result | Projects displayed in table format |
| Pass Criteria | All columns display correctly |

#### TC-PM-003: Search Projects

| Field | Value |
|-------|-------|
| Test ID | TC-PM-003 |
| Description | Search for projects by name |
| Preconditions | Projects exist in database |
| Steps | 1. Navigate to Projects page |
| | 2. Enter search term |
| | 3. Click Search |
| Expected Result | Matching projects displayed |
| Pass Criteria | Only matching projects shown |

#### TC-PM-004: Filter Projects by Status

| Field | Value |
|-------|-------|
| Test ID | TC-PM-004 |
| Description | Filter projects by status |
| Preconditions | Projects exist with different statuses |
| Steps | 1. Navigate to Projects page |
| | 2. Click status filter link |
| Expected Result | Only projects with selected status shown |
| Pass Criteria | Correct filtering applied |

#### TC-PM-005: Edit Project

| Field | Value |
|-------|-------|
| Test ID | TC-PM-005 |
| Description | Modify existing project |
| Preconditions | Project exists |
| Steps | 1. Navigate to Projects page |
| | 2. Click Edit on project |
| | 3. Modify fields |
| | 4. Click Save |
| Expected Result | Project updated |
| Pass Criteria | Changes reflected in list |

#### TC-PM-006: Delete Project

| Field | Value |
|-------|-------|
| Test ID | TC-PM-006 |
| Description | Delete a project |
| Preconditions | Test project exists |
| Steps | 1. Navigate to project edit |
| | 2. Click Delete |
| | 3. Confirm deletion |
| Expected Result | Project removed |
| Pass Criteria | Project no longer in list |

#### TC-PM-007: Link Project to Customer

| Field | Value |
|-------|-------|
| Test ID | TC-PM-007 |
| Description | Link project to FA customer |
| Preconditions | Customer exists in debtors_master |
| Steps | 1. Create/edit project |
| | 2. Select customer from dropdown |
| | 3. Save |
| Expected Result | Customer linked |
| Pass Criteria | Customer shows in project details |

---

### 3.2 Task Management Tests

#### TC-TM-001: Create Task

| Field | Value |
|-------|-------|
| Test ID | TC-TM-001 |
| Description | Create a new task under a project |
| Preconditions | Project exists, user has PM_MANAGE_TASKS |
| Steps | 1. Navigate to Tasks page |
| | 2. Click "New Task" |
| | 3. Fill required fields |
| | 4. Save |
| Expected Result | Task saved and associated with project |
| Pass Criteria | Task visible in task list |

#### TC-TM-002: View Tasks by Project

| Field | Value |
|-------|-------|
| Test ID | TC-TM-002 |
| Description | View tasks filtered by project |
| Preconditions | Tasks exist under projects |
| Steps | 1. Navigate to Tasks page |
| | 2. Select project from filter |
| Expected Result | Only tasks from selected project shown |
| Pass Criteria | Correct filtering |

#### TC-TM-003: Update Task Progress

| Field | Value |
|-------|-------|
| Test ID | TC-TM-003 |
| Description | Update task completion percentage |
| Preconditions | Task exists |
| Steps | 1. Edit task |
| | 2. Update progress value |
| | 3. Save |
| Expected Result | Progress updated |
| Pass Criteria | Progress displays in view |

#### TC-TM-004: Task Overdue Detection

| Field | Value |
|-------|-------|
| Test ID | TC-TM-004 |
| Description | Overdue tasks highlighted |
| Preconditions | Task with past end_date and incomplete status |
| Steps | 1. View task list |
| Expected Result | Overdue task highlighted in red |
| Pass Criteria | Correct visual indication |

---

### 3.3 Team Management Tests

#### TC-TMG-001: Assign Employee to Project

| Field | Value |
|-------|-------|
| Test ID | TC-TMG-001 |
| Description | Add employee to project team |
| Preconditions | Project exists, employee exists |
| Steps | 1. Navigate to Team page |
| | 2. Select project |
| | 3. Add employee with role and allocation |
| | 4. Save |
| Expected Result | Employee assigned to project |
| Pass Criteria | Employee appears in team list |

#### TC-TMG-002: View Project Team

| Field | Value |
|-------|-------|
| Test ID | TC-TMG-002 |
| Description | View team members for a project |
| Preconditions | Project has team members |
| Steps | 1. Navigate to Team page |
| | 2. Select project |
| Expected Result | Team members displayed |
| Pass Criteria | All assigned members shown |

#### TC-TMG-003: Remove Employee from Project

| Field | Value |
|-------|-------|
| Test ID | TC-TMG-003 |
| Description | Remove employee from team |
| Preconditions | Employee is assigned to project |
| Steps | 1. Navigate to team management |
| | 2. Remove employee assignment |
| Expected Result | Employee removed |
| Pass Criteria | Employee not in team list |

---

### 3.4 Dashboard Tests

#### TC-DB-001: Dashboard Statistics

| Field | Value |
|-------|-------|
| Test ID | TC-DB-001 |
| Description | Verify dashboard shows correct statistics |
| Preconditions | Data exists in database |
| Steps | 1. Navigate to Dashboard |
| Expected Result | Dashboard shows counts |
| Pass Criteria | Total, Active, Pending, Overdue counts correct |

#### TC-DB-002: Recent Activities

| Field | Value |
|-------|-------|
| Test ID | TC-DB-002 |
| Description | Verify recent activities display |
| Preconditions | Activities logged |
| Steps | 1. Navigate to Dashboard |
| Expected Result | Recent activities listed |
| Pass Criteria | Last 5-10 activities shown |

---

### 3.5 Report Tests

#### TC-RP-001: Project Summary Report

| Field | Value |
|-------|-------|
| Test ID | TC-RP-001 |
| Description | Generate project status summary |
| Preconditions | Projects exist |
| Steps | 1. Navigate to Reports |
| | 2. Select Project Summary |
| Expected Result | Status breakdown displayed |
| Pass Criteria | Counts by status accurate |

---

### 3.6 Import Tests

#### TC-IM-001: Import Projects from CSV

| Field | Value |
|-------|-------|
| Test ID | TC-IM-001 |
| Description | Bulk import projects |
| Preconditions | Valid CSV file prepared |
| Steps | 1. Navigate to Import |
| | 2. Upload CSV file |
| | 3. Execute import |
| Expected Result | Projects created |
| Pass Criteria | Projects in list match CSV |

#### TC-IM-002: Update Projects via Import

| Field | Value |
|-------|-------|
| Test ID | TC-IM-002 |
| Description | Update existing projects via import |
| Preconditions | Projects exist, CSV with matching IDs |
| Steps | 1. Upload CSV with existing project IDs |
| | 2. Execute import |
| Expected Result | Projects updated |
| Pass Criteria | Data matches CSV |

---

### 3.7 Security Tests

#### TC-SC-001: Permission - View Project

| Field | Value |
|-------|-------|
| Test ID | TC-SC-001 |
| Description | User without permission cannot view projects |
| Preconditions | User lacks PM_VIEW_PROJECT |
| Steps | 1. User attempts to access Projects page |
| Expected Result | Access denied error |
| Pass Criteria | Error message displayed |

#### TC-SC-002: Permission - Manage Project

| Field | Value |
|-------|-------|
| Test ID | TC-SC-002 |
| Description | User without permission cannot create projects |
| Preconditions | User lacks PM_MANAGE_PROJECT |
| Steps | 1. User attempts to create project |
| Expected Result | Access denied error |
| Pass Criteria | Error message displayed |

---

### 3.8 Integration Tests

#### TC-INT-001: CRM Integration

| Field | Value |
|-------|-------|
| Test ID | TC-INT-001 |
| Description | Customer dropdown populated from FA CRM |
| Preconditions | Customers exist in debtors_master |
| Steps | 1. Navigate to project create/edit |
| | 2. View customer dropdown |
| Expected Result | Customers from FA loaded |
| Pass Criteria | Customer names displayed |

#### TC-INT-002: Employee Integration

| Field | Value |
|-------|-------|
| Test ID | TC-INT-002 |
| Description | Employee data from FA Employee Management |
| Preconditions | Employees exist |
| Steps | 1. View team page |
| | 2. View employee details |
| Expected Result | Employee data displayed |
| Pass Criteria | Name, email, job title shown |

---

## 4. Test Execution

### 4.1 Execution Order

1. Unit tests (via phpunit)
2. Integration tests
3. System tests
4. UAT

### 4.2 Test Results Template

| Test ID | Test Name | Status | Notes |
|---------|-----------|--------|-------|
| TC-PM-001 | Create New Project | PASS/FAIL | |
| TC-PM-002 | View Project List | PASS/FAIL | |

### 4.3 Defect Reporting

| Field | Description |
|-------|-------------|
| Defect ID | Unique identifier |
| Test ID | Related test case |
| Severity | Critical/Major/Minor |
| Description | Detailed description |
| Steps to Reproduce | Reproduction steps |
| Expected Result | What should happen |
| Actual Result | What actually happened |

---

## 5. Acceptance Criteria

### 5.1 Functional Acceptance

| Requirement ID | Description | Test Coverage |
|----------------|-------------|---------------|
| FR-1.1 | Create Project | TC-PM-001 |
| FR-1.2 | View Projects | TC-PM-002 |
| FR-1.3 | Edit Project | TC-PM-005 |
| FR-1.4 | Delete Project | TC-PM-006 |
| FR-2.1 | Create Task | TC-TM-001 |
| FR-2.2 | View Tasks | TC-TM-002 |
| FR-3.1 | Assign Employee | TC-TMG-001 |
| FR-4.1 | Dashboard | TC-DB-001 |
| FR-5.1 | Import | TC-IM-001 |

### 5.2 Non-Functional Acceptance

| Criteria | Target |
|----------|--------|
| Page Load Time | < 3 seconds |
| Database Queries | < 10 per page |
| Browser Compatibility | Chrome, Firefox, Edge |
| Access Control | All permissions enforced |
| Data Validation | All inputs validated |

---

## 6. Test Deliverables

| Deliverable | Description |
|-------------|-------------|
| Test Cases | This document |
| Test Data | Sample data for testing |
| Test Results | Execution results log |
| Defect Log | Issues found during testing |
| Test Summary | Final pass/fail report |

---

## 7. Test Schedule

| Phase | Duration | Activities |
|-------|----------|-----------|
| Unit Testing | 1 day | phpunit execution |
| Integration Testing | 2 days | Integration tests |
| System Testing | 3 days | End-to-end workflows |
| UAT | 5 days | User acceptance |
| Bug Fixing | Ongoing | Fix and retest |

---

## 8.风险的 Management

### 8.1 Test Risks

| Risk | Mitigation |
|------|-------------|
| Test data not available | Create sample data first |
| Environment issues | Use isolated test environment |
| Scope creep | Track changes to requirements |

---
