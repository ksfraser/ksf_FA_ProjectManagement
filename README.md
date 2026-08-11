# ksf_FA_ProjectManagement - Project Management Module

FA module for managing projects, tasks, team assignments, and project-related activities.

## Overview

The Project Management module provides comprehensive functionality for project-based work management including:
- Project creation and lifecycle management
- Task management with hierarchical structure
- Team assignment and resource allocation
- Customer linking from FA CRM
- Activity tracking and reporting

## Features

### Core Features

#### Project Management
- **Full CRUD Operations**: Create, read, update, delete projects
- **Project Types**: Classify projects by type (Software Development, Infrastructure, Consulting, Research, Marketing, Event)
- **Status Tracking**: Planning, Active, On Hold, Completed, Cancelled
- **Priority Levels**: Low, Medium, High, Critical
- **Budget Management**: Track project budgets with actual costs
- **Customer Integration**: Link projects to FA CRM customers (debtors)
- **Manager Assignment**: Assign project managers from employees

#### Task Management
- **Hierarchical Tasks**: Parent-child task relationships
- **Task Assignment**: Assign tasks to employees
- **Progress Tracking**: Track percentage completion (0-100%)
- **Time Tracking**: Estimated vs actual hours
- **Status Workflow**: Not Started, In Progress, On Hold, Completed, Cancelled
- **Priority Levels**: Low, Medium, High, Critical
- **Due Date Management**: Start date, end date, overdue detection

#### Team Management
- **Employee Assignments**: Assign employees to projects
- **Role Management**: Define roles (Team Member, Developer, Designer, etc.)
- **Resource Allocation**: Percentage-based allocation (0-100%)
- **Time Period**: Start and end dates for assignments
- **Employee Directory**: Integration with FA Employee Management

#### Dashboard & Reporting
- **Dashboard Statistics**: Total projects, active projects, pending tasks, overdue tasks
- **Recent Activities**: Audit trail of all project-related actions
- **Project Summary Reports**: Status breakdown counts
- **Task Analysis Reports**: Task status and progress tracking
- **Resource Utilization**: Team member workload tracking

#### Import/Export
- **CSV Import**: Bulk import projects from CSV files
- **Update Mode**: Upsert functionality (create or update)
- **Field Mapping**: Support for all project fields

#### File Management
- **Document Attachments**: Attach files to projects and tasks
- **Storage Options**: Local storage, S3, FA attachments
- **Version Tracking**: Track upload history

### Integration Features
- **Customer Integration**: Link to FA CRM debtors
- **Employee Integration**: Link to FA employee records
- **Event-Driven Architecture**: PSR-14 event dispatcher
- **Dependency Injection**: PSR-11 container support

## Quick Start

### Installation

```bash
# Install via composer
composer require ksfraser/ksf-project-management

# Copy module to FA
cp -r ksf_FA_ProjectManagement /path/to/frontaccounting/modules/

# Activate via FA Admin → Setup → Modules
```

### Basic Usage

```php
use Ksfraser\ProjectManagement\FA\PMContainer;
use Ksfraser\ProjectManagement\ProjectService;

// Create a project
$projectData = [
    'project_id' => 'PRJ-001',
    'name' => 'Website Redesign',
    'description' => 'Corporate website redesign project',
    'start_date' => '2024-01-01',
    'end_date' => '2024-06-30',
    'budget' => 50000.00,
    'project_manager' => 'EMP-001',
    'priority' => 'High',
    'status' => 'Planning'
];

$projectId = insert_pm_project($projectData);

// Get project details
$project = get_pm_project('PRJ-001');

// Add a task
$taskData = [
    'task_id' => 'PRJ-001-TASK-001',
    'project_id' => 'PRJ-001',
    'name' => 'Design mockups',
    'assigned_to' => 'EMP-002',
    'estimated_hours' => 40,
    'priority' => 'High'
];

$taskId = insert_pm_task($taskData);

// Assign employee to project
assign_employee_to_project([
    'project_id' => 'PRJ-001',
    'employee_id' => 'EMP-002',
    'role' => 'Designer',
    'allocation_percentage' => 50
]);
```

## Database Tables

The module expects the following database tables:

### fa_pm_projects
| Column | Type | Description |
|--------|------|-------------|
| project_id | VARCHAR(20) | Primary key |
| name | VARCHAR(100) | Project name |
| description | TEXT | Detailed description |
| start_date | DATE | Project start date |
| end_date | DATE | Project end date |
| budget | DECIMAL(15,2) | Project budget |
| customer_id | VARCHAR(20) | FK to debtors_master |
| project_manager | VARCHAR(100) | Employee ID |
| priority | VARCHAR(20) | Low/Medium/High/Critical |
| status | VARCHAR(30) | Planning/Active/On Hold/Completed/Cancelled |
| project_type_id | INT | FK to project_types |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

### fa_pm_tasks
| Column | Type | Description |
|--------|------|-------------|
| task_id | VARCHAR(20) | Primary key |
| project_id | VARCHAR(20) | FK to projects |
| parent_task_id | VARCHAR(20) | Parent task for hierarchy |
| name | VARCHAR(100) | Task name |
| description | TEXT | Detailed description |
| assigned_to | VARCHAR(100) | Employee ID |
| start_date | DATE | Task start date |
| end_date | DATE | Task due date |
| estimated_hours | DECIMAL(10,2) | Estimated hours |
| actual_hours | DECIMAL(10,2) | Actual hours spent |
| progress | DECIMAL(5,2) | Completion percentage (0-100) |
| priority | VARCHAR(20) | Low/Medium/High/Critical |
| status | VARCHAR(30) | Not Started/In Progress/On Hold/Completed/Cancelled |
| created_at | TIMESTAMP | Record creation time |
| updated_at | TIMESTAMP | Last update time |

### fa_pm_assignments
| Column | Type | Description |
|--------|------|-------------|
| project_id | VARCHAR(20) | FK to projects (PK) |
| employee_id | VARCHAR(100) | FK to employees (PK) |
| role | VARCHAR(50) | Role on project |
| start_date | DATE | Assignment start |
| end_date | DATE | Assignment end |
| allocation_percentage | DECIMAL(5,2) | Work allocation % |
| created_at | TIMESTAMP | Record creation time |

### fa_pm_project_types
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| name | VARCHAR(50) | Type name |
| description | VARCHAR(255) | Type description |
| inactive | TINYINT(1) | Active flag |
| sort_order | INT(11) | Display order |

Initial types: Software Development, Infrastructure, Consulting, Research, Marketing, Event

### fa_pm_activity_log
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| activity_type | VARCHAR(30) | Activity category |
| entity_type | VARCHAR(30) | project/task/assignment |
| entity_id | VARCHAR(20) | Entity reference |
| user_id | VARCHAR(100) | User who performed action |
| action | VARCHAR(50) | Action performed |
| details | TEXT | Detailed description |
| old_values | TEXT | Previous values (JSON) |
| new_values | TEXT | New values (JSON) |
| ip_address | VARCHAR(45) | Client IP |
| created_at | TIMESTAMP | Activity timestamp |

### fa_pm_files
| Column | Type | Description |
|--------|------|-------------|
| id | INT(11) | Primary key |
| entity_type | VARCHAR(30) | project/task |
| entity_id | VARCHAR(20) | Entity reference |
| file_name | VARCHAR(255) | Stored file name |
| original_name | VARCHAR(255) | Original file name |
| mime_type | VARCHAR(100) | MIME type |
| size | INT(11) | File size in bytes |
| storage_type | VARCHAR(20) | local/s3/fa_attachment |
| storage_path | VARCHAR(500) | Storage location |
| uploaded_by | VARCHAR(100) | Uploader user ID |
| uploaded_at | DATETIME | Upload timestamp |
| description | TEXT | File description |
| inactive | TINYINT(1) | Active flag |

## Permissions

### Role-Based Access Control

| Permission | Description |
|------------|-------------|
| PM_VIEW_PROJECT | View project list and details |
| PM_MANAGE_PROJECT | Create, edit, delete projects |
| PM_VIEW_TASKS | View task list and details |
| PM_MANAGE_TASKS | Create, edit, delete tasks |
| PM_VIEW_TEAM | View team assignments |
| PM_MANAGE_TEAM | Manage team assignments |
| PM_VIEW_REPORTS | View reports and analytics |
| PM_ADMIN | Full administrative access |

## API Reference

### Database Functions (pm_db.inc)

```php
// Projects
get_pm_projects(string $search = '', string $status = ''): object|false
get_pm_project(string $projectId): ?array
get_pm_project_count(string $status = ''): int
insert_pm_project(array $data): string
update_pm_project(string $projectId, array $data): bool
delete_pm_project(string $projectId): bool

// Tasks
get_pm_tasks(string $projectId = ''): object|false
get_pm_task(string $taskId): ?array
get_pm_task_count(string $status = ''): int
get_pm_overdue_task_count(): int
insert_pm_task(array $data): string
update_pm_task(string $taskId, array $data): bool

// Team
get_pm_project_team(string $projectId): array
assign_employee_to_project(array $data): bool
remove_employee_from_project(string $projectId, string $employeeId): bool

// Activities
get_pm_recent_activities(int $limit = 10): array
```

### UI Functions (pm_ui.inc)

```php
// Navigation
pm_navigation_menu(): void

// Display
display_pm_dashboard_stats(array $stats): void
display_pm_stat_cell(string $label, int $value, string $type): void
display_pm_recent_activities(): void

// Select Helpers
sel_priority(string $selected = 'Medium'): string
sel_project_status(string $selected = 'Planning'): string
sel_task_status(string $selected = 'Not Started'): string
sel_project(array $projects, string $selected = ''): string
sel_customer(string $selected = ''): string
sel_project_type(string $selected = ''): string

// Status Helpers
get_pm_priority_class(string $priority): string
get_pm_status_class(string $status): string
```

### Import Functions (import.php)

```php
ksf_render_pm_import(): void
```

### Container Services (PMContainer.php)

```php
// Services available via DI container:
// - DatabaseAdapterInterface
// - EmployeeServiceInterface
// - ProjectServiceInterface
// - EventDispatcherInterface
// - LoggerInterface
```

## Configuration

### Project Status Flow

```
Planning → Active → Completed
Planning → Active → On Hold → Active
Planning → Active → Completed → Cancelled
Planning → Active → Cancelled
```

### Task Status Flow

```
Not Started → In Progress → Completed
Not Started → In Progress → On Hold → In Progress
Not Started → In Progress → Completed → Cancelled
Not Started → Cancelled
```

### Priority Levels (Sequential Processing Order)

1. Critical - Must be addressed immediately
2. High - Important, should be addressed soon
3. Medium - Standard priority
4. Low - Can be addressed when time permits

## Testing

Run unit tests:

```bash
./vendor/bin/phpunit
```

## Module Structure

```
ksf_FA_ProjectManagement/
├── composer.json
├── FA_PM_Module.php
├── hooks.php
├── pm.php
├── README.md
├── _init/
│   └── init.inc
├── includes/
│   ├── import.php
│   ├── PMContainer.php
│   ├── pm_db.inc
│   └── pm_ui.inc
├── pages/
│   ├── dashboard.php
│   ├── projects.php
│   ├── tasks.php
│   ├── team.php
│   ├── reports.php
│   └── settings.php
├��─ sql/
│   ├── install.sql
│   └── uninstall.sql
├── tests/
│   └── Unit/
│       ├── ComposerDependencyManagerTest.php
│       └── MetadataTest.php
└── ProjectDcs/
    ├── Architecture.md
    ├── Business Requirements.md
    ├── Functional Requirements.md
    ├── RTM.md
    ├── Test Plan.md
    ├── UAT Plan.md
    └── Use Case.md
```

## Dependencies

- FrontAccounting 2.4.0+
- PHP 8.0+
- ksfraser/ksf-project-management (core library)
- ksfraser/ksf-common (common utilities)

## License

Proprietary - KS Fraser Application Framework
