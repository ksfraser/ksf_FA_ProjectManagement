# Functional Requirements - ksf_FA_ProjectManagement

## Overview

This document details the functional requirements for the Project Management module (ksf_FA_ProjectManagement), which provides FA-specific project, task, and team management functionality.

## Scope

The module handles:
- Project lifecycle management
- Task hierarchy and assignment
- Team member allocation
- Customer integration (FA CRM)
- Activity logging and reporting
- File/document management

---

## FR-1: Project Management

### FR-1.1: Create Project

**Description**: Users shall be able to create new projects with all required fields.

**Requirements**:
- FR-1.1.1: System shall accept project ID, name, description, start date
- FR-1.1.2: System shall accept optional fields: end date, budget, customer, manager, priority, status
- FR-1.1.3: System shall validate required fields are not empty
- FR-1.1.4: System shall generate unique project ID if not provided
- FR-1.1.5: System shall set default status to "Planning"
- FR-1.1.6: System shall set default priority to "Medium"
- FR-1.1.7: System shall generate activity log entry on creation
- FR-1.1.8: System shall link to customer from FA debtors_master table

**Acceptance Criteria**:
- [ ] Project can be created with all required fields
- [ ] Optional fields are stored correctly
- [ ] Default values are applied when not specified
- [ ] Customer linking works via customer_id foreign key

### FR-1.2: View Projects

**Description**: Users shall be able to view project list and details.

**Requirements**:
- FR-1.2.1: System shall display project list with key fields
- FR-1.2.2: System shall support search by name or description
- FR-1.2.3: System shall support filtering by status
- FR-1.2.4: System shall display project manager name from employee table
- FR-1.2.5: System shall display priority with color coding
- FR-1.2.6: System shall display status with color coding
- FR-1.2.7: System shall support pagination for large datasets
- FR-1.2.8: System shall sort by start date descending by default

**Acceptance Criteria**:
- [ ] All projects are listed with correct columns
- [ ] Search returns matching projects
- [ ] Status filter shows only matching projects
- [ ] Color coding reflects priority/status values

### FR-1.3: Edit Project

**Description**: Users shall be able to modify existing project details.

**Requirements**:
- FR-1.3.1: System shall pre-populate form with existing values
- FR-1.3.2: System shall validate required fields
- FR-1.3.3: System shall track old values before update
- FR-1.3.4: System shall generate activity log entry with changes
- FR-1.3.5: System shall update timestamp on modification

**Acceptance Criteria**:
- [ ] Form pre-fills with current values
- [ ] Changes are saved to database
- [ ] Activity log shows what changed

### FR-1.4: Delete Project

**Description**: Users shall be able to delete projects.

**Requirements**:
- FR-1.4.1: System shall require confirmation before deletion
- FR-1.4.2: System shall cascade delete related tasks
- FR-1.4.3: System shall cascade delete related assignments
- FR-1.4.4: System shall generate activity log entry

**Acceptance Criteria**:
- [ ] Confirmation dialog appears
- [ ] Deletion removes project and all related data
- [ ] Activity is logged

### FR-1.5: Project Status Management

**Description**: System shall support project status workflow.

**Requirements**:
- FR-1.5.1: System shall support status values: Planning, Active, On Hold, Completed, Cancelled
- FR-1.5.2: System shall allow status changes by authorized users
- FR-1.5.3: System shall update related task status when project is completed/cancelled
- FR-1.5.4: System shall display status with appropriate color coding

**Acceptance Criteria**:
- [ ] Status dropdown shows all valid values
- [ ] Status changes are saved
- [ ] Color coding: Completed=green, Active=blue, On Hold=orange, Cancelled=red

---

## FR-2: Task Management

### FR-2.1: Create Task

**Description**: Users shall be able to create tasks within projects.

**Requirements**:
- FR-2.1.1: System shall require project_id for task creation
- FR-2.1.2: System shall accept task ID, name, description
- FR-2.1.3: System shall accept optional: parent_task_id for hierarchy
- FR-2.1.4: System shall accept assignment to employee
- FR-2.1.5: System shall accept start date and end date
- FR-2.1.6: System shall accept estimated hours
- FR-2.1.7: System shall default progress to 0%
- FR-2.1.8: System shall default status to "Not Started"
- FR-2.1.9: System shall default priority to "Medium"
- FR-2.1.10: System shall generate activity log entry

**Acceptance Criteria**:
- [ ] Task can be created with all required fields
- [ ] Parent task relationship is established
- [ ] Task assigned to employee correctly

### FR-2.2: View Tasks

**Description**: Users shall be able to view task list.

**Requirements**:
- FR-2.2.1: System shall display all tasks optionally filtered by project
- FR-2.2.2: System shall show project name for context
- FR-2.2.3: System shall show status with color coding
- FR-2.2.4: System shall show progress as visual bar + percentage
- FR-2.2.5: System shall highlight overdue tasks in red
- FR-2.2.6: System shall show assigned employee
- FR-2.2.7: System shall support sorting by various fields

**Acceptance Criteria**:
- [ ] Tasks displayed in table format
- [ ] Progress bar shows correctly
- [ ] Overdue tasks highlighted

### FR-2.3: Edit Task

**Description**: Users shall be able to modify task details.

**Requirements**:
- FR-2.3.1: System shall pre-populate form with existing values
- FR-2.3.2: System shall allow updating actual hours
- FR-2.3.3: System shall allow updating progress (0-100%)
- FR-2.3.4: System shall generate activity log on changes
- FR-2.3.5: System shall auto-update status based on progress

**Acceptance Criteria**:
- [ ] Form pre-fills with current values
- [ ] Progress saves correctly
- [ ] Activity logged

### FR-2.4: Delete Task

**Description**: Users shall be able to delete tasks.

**Requirements**:
- FR-2.4.1: System shall require confirmation before deletion
- FR-2.4.2: System shall handle child tasks (re-parent or prevent)
- FR-2.4.3: System shall generate activity log entry

**Acceptance Criteria**:
- [ ] Confirmation appears
- [ ] Child tasks handled appropriately

### FR-2.5: Task Hierarchy

**Description**: System shall support hierarchical task structure.

**Requirements**:
- FR-2.5.1: System shall support parent_task_id for sub-tasks
- FR-2.5.2: System shall query tasks by parent for tree display
- FR-2.5.3: System shall calculate aggregate progress from children
- FR-2.5.4: System shall calculate aggregate hours from children

**Acceptance Criteria**:
- [ ] Parent/child relationship displayed
- [ ] Aggregate calculations work

---

## FR-3: Team Management

### FR-3.1: Assign Employee to Project

**Description**: Users shall be able to assign employees to projects.

**Requirements**:
- FR-3.1.1: System shall require project_id and employee_id
- FR-3.1.2: System shall accept role (default: "Team Member")
- FR-3.1.3: System shall accept allocation percentage (default: 100)
- FR-3.1.4: System shall accept start date (default: today)
- FR-3.1.5: System shall accept end date (optional)
- FR-3.1.6: System shall prevent duplicate assignments
- FR-3.1.7: System shall validate employee exists in FA employee table

**Acceptance Criteria**:
- [ ] Employee assigned to project
- [ ] Role and allocation stored
- [ ] Duplicate prevented

### FR-3.2: View Project Team

**Description**: Users shall be able to view team members for a project.

**Requirements**:
- FR-3.2.1: System shall list all assigned employees
- FR-3.2.2: System shall show employee name, email, job title
- FR-3.2.3: System shall show role and allocation percentage
- FR-3.2.4: System shall filter by active assignments (end_date >= today or null)

**Acceptance Criteria**:
- [ ] Team members displayed correctly
- [ ] Only active members shown by default

### FR-3.3: Remove Employee from Project

**Description**: Users shall be able to remove employees from projects.

**Requirements**:
- FR-3.3.1: System shall remove assignment record
- FR-3.3.2: System shall handle active task reassignment
- FR-3.3.3: System shall generate activity log entry

**Acceptance Criteria**:
- [ ] Employee removed from project
- [ ] Activity logged

---

## FR-4: Dashboard & Reporting

### FR-4.1: Dashboard Statistics

**Description**: System shall display project management dashboard.

**Requirements**:
- FR-4.1.1: System shall display total project count
- FR-4.1.2: System shall display active project count
- FR-4.1.3: System shall display pending task count (Not Started)
- FR-4.1.4: System shall display overdue task count
- FR-4.1.5: System shall display recent activities (last 5-10)

**Acceptance Criteria**:
- [ ] All statistics display correctly
- [ ] Recent activities show latest actions

### FR-4.2: Project Summary Report

**Description**: System shall generate project status summary reports.

**Requirements**:
- FR-4.2.1: System shall count projects by status
- FR-4.2.2: System shall display breakdown table
- FR-4.2.3: System shall support date range filtering

**Acceptance Criteria**:
- [ ] Status counts accurate
- [ ] Table displays correctly

### FR-4.3: Task Analysis Report

**Description**: System shall generate task analysis reports.

**Requirements**:
- FR-4.3.1: System shall count tasks by status
- FR-4.3.2: System shall show progress distribution
- FR-4.3.3: System shall identify overdue tasks

**Acceptance Criteria**:
- [ ] Task counts accurate
- [ ] Overdue tasks identified

### FR-4.4: Resource Utilization Report

**Description**: System shall generate resource utilization reports.

**Requirements**:
- FR-4.4.1: System shall show employee workload
- FR-4.4.2: System shall calculate total allocation per employee
- FR-4.4.3: System shall show project distribution

**Acceptance Criteria**:
- [ ] Workload calculations correct
- [ ] Per-employee totals accurate

---

## FR-5: Import Functionality

### FR-5.1: Import Projects

**Description**: System shall support bulk import of projects from CSV.

**Requirements**:
- FR-5.1.1: System shall accept CSV file upload
- FR-5.1.2: System shall support target fields: project_no, name, description, customer, start_date, due_date, status, priority, assigned_to, hours, budget
- FR-5.1.3: System shall support update mode for existing projects
- FR-5.1.4: System shall validate required fields
- FR-5.1.5: System shall report import results

**Acceptance Criteria**:
- [ ] CSV file uploads successfully
- [ ] Projects created/updated
- [ ] Validation errors reported

---

## FR-6: Activity Logging

### FR-6.1: Track Activities

**Description**: System shall log all project-related activities.

**Requirements**:
- FR-6.1.1: System shall log project CRUD operations
- FR-6.1.2: System shall log task CRUD operations
- FR-6.1.3: System shall log team assignment changes
- FR-6.1.4: System shall capture user_id, action, details
- FR-6.1.5: System shall capture old/new values
- FR-6.1.6: System shall capture IP address is performed from
- FR-6.1.7: System shall capture timestamp

**Acceptance Criteria**:
- [ ] All major operations logged
- [ ] Audit trail complete

---

## FR-7: Settings & Configuration

### FR-7.1: Module Settings

**Description**: System shall provide module configuration options.

**Requirements**:
- FR-7.1.1: System shall allow configuration of default project statuses
- FR-7.1.2: System shall allow configuration of default task statuses
- FR-7.1.3: System shall allow notification settings
- FR-7.1.4: System shall allow integration settings

**Acceptance Criteria**:
- [ ] Settings page accessible to admins
- [ ] Settings persist correctly

---

## FR-8: Integration

### FR-8.1: FA CRM Integration

**Description**: System shall integrate with FA CRM customer management.

**Requirements**:
- FR-8.1.1: System shall link projects to customers (debtors)
- FR-8.1.2: System shall populate customer dropdown from debtors_master
- FR-8.1.3: System shall use customer name for display
- FR-8.1.4: System shall handle customer deletion gracefully

**Acceptance Criteria**:
- [ ] Customer dropdown populated
- [ ] Projects linked to customers

### FR-8.2: Employee Management Integration

**Description**: System shall integrate with FA Employee Management.

**Requirements**:
- FR-8.2.1: System shall link managers from employees table
- FR-8.2.2: System shall link assignees from employees table
- FR-8.2.3: System shall display employee names
- FR-8.2.4: System shall validate employee exists

**Acceptance Criteria**:
- [ ] Employee dropdowns populated
- [ ] Valid employee checks work

### FR-8.3: Container/DI Integration

**Description**: System shall support dependency injection.

**Requirements**:
- FR-8.3.1: System shall implement PSR-11 ContainerInterface
- FR-8.3.2: System shall provide DatabaseAdapterInterface
- FR-8.3.3: System shall provide EmployeeServiceInterface
- FR-8.3.4: System shall provide ProjectServiceInterface
- FR-8.3.5: System shall implement PSR-14 EventDispatcherInterface

**Acceptance Criteria**:
- [ ] Container properly resolves services
- [ ] Event dispatching works

---

## Appendix: Requirement ID Index

| ID | Description |
|----|-------------|
| FR-1.1 | Create Project |
| FR-1.2 | View Projects |
| FR-1.3 | Edit Project |
| FR-1.4 | Delete Project |
| FR-1.5 | Project Status Management |
| FR-2.1 | Create Task |
| FR-2.2 | View Tasks |
| FR-2.3 | Edit Task |
| FR-2.4 | Delete Task |
| FR-2.5 | Task Hierarchy |
| FR-3.1 | Assign Employee to Project |
| FR-3.2 | View Project Team |
| FR-3.3 | Remove Employee from Project |
| FR-4.1 | Dashboard Statistics |
| FR-4.2 | Project Summary Report |
| FR-4.3 | Task Analysis Report |
| FR-4.4 | Resource Utilization Report |
| FR-5.1 | Import Projects |
| FR-6.1 | Track Activities |
| FR-7.1 | Module Settings |
| FR-8.1 | FA CRM Integration |
| FR-8.2 | Employee Management Integration |
| FR-8.3 | Container/DI Integration |
