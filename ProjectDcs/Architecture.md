# Architecture - ksf_FA_ProjectManagement

## Overview

This document describes the technical architecture for the Project Management module, including the layered architecture, component design, database schema, and integration patterns.

---

## 1. System Architecture

### 1.1 High-Level Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Presentation Layer                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐   │
│  │Dashboard │ │Projects │ │ Tasks   │ │ Team    │   │
│  │   Page   │ │  Page   │ │  Page   │ │  Page   │   │
│  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘   │
│       │           │           │           │           │         │
│       └───────────┴───────────┴───────────┘           │
│                         │                             │
├─────────────────────────┼─────────────────────────────┤
│                    Service Layer                      │
│  ┌──────────────────────────────────────────────────┐  │
│  │                pm_db.inc                        │  │
│  │   Database functions (CRUD operations)          │  │
│  └──────────────────────────────────────────────────┘  │
│  ┌──────────────────────────────────────────────────┐  │
│  │                pm_ui.inc                         │  │
│  │   UI helper functions and display logic          │  │
│  └──────────────────────────────────────────────────┘  │
├──────────────────────────────────────────────────────────┤
│                    Business Layer                       │
│  ┌──────────────────────────────────────────────────┐  │
│  │              PMContainer (DI Container)          │  │
│  │   - ProjectService                              │  │
│  │   - EmployeeService                          │  │
│  │   - DatabaseAdapter                         │  │
│  └──────────────────────────────────────────────────┘  │
├──────────────────────────────────────────────────────────┤
│                    Data Layer                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐              │
│  │Projects  │ │ Tasks    │ │Assignments│              │
│  │  Table   │ │  Table   │ │   Table  │              │
│  └──────────┘ └──────────┘ └──────────┘              │
├──────────────────────────────────────────────────────────┤
│                  Integration Layer                      │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐              │
│  │FA CRM    │ │Employee │ │ksf-PM   │              │
│  │(Debtors) │ │  Mgmt   │ │ Library │              │
│  └──────────┘ └──────────┘ └──────────┘              │
└─────────────────────────────────────────────────────────────┘
```

### 1.2 Module Structure

```
ksf_FA_ProjectManagement/
├── FA_PM_Module.php        # Module class with permissions
├── hooks.php              # FA lifecycle hooks
├── pm.php                # API controller
├── _init/
│   └── init.inc         # Module initialization
├── includes/
│   ├── import.php       # Import functionality
│   ├── PMContainer.php # DI container & services
│   ├── pm_db.inc       # Database functions
│   └── pm_ui.inc       # UI helpers
├── pages/
│   ├── dashboard.php    # Dashboard view
│   ├── projects.php  # Project CRUD
│   ├── tasks.php    # Task CRUD
│   ├── team.php    # Team management
│   ├── reports.php # Reporting
│   └── settings.php # Settings
└── sql/
    ├── install.sql   # Schema creation
    └── uninstall.sql # Schema removal
```

---

## 2. Component Design

### 2.1 Core Components

#### PMContainer
The DI container provides service instantiation and dependency management.

**Purpose**: Central service locator implementing PSR-11 ContainerInterface

**Services Provided**:
- `DatabaseAdapterInterface` - FADatabaseAdapter
- `EmployeeServiceInterface` - FAEmployeeService
- `ProjectServiceInterface` / `ProjectService` - Core business logic
- `EventDispatcherInterface` - FAEventDispatcher (PSR-14)
- `LoggerInterface` - NullLogger (PSR-3)

**Responsibilities**:
- Service instantiation on demand
- Dependency injection into services
- Service lifecycle management

```php
class PMContainer implements ContainerInterface
{
    public function get(string $id): mixed
    public function has(string $id): bool
}
```

#### FADatabaseAdapter
Wraps FA database functions for use by services.

**Methods**:
```php
interface DatabaseAdapterInterface
{
    public function fetchAssoc(string $sql, array $params = []): ?array
    public function fetchAll(string $sql, array $params = []): array
    public function executeUpdate(string $sql, array $params = []): int
    public function lastInsertId(): string|false
}
```

#### FAEmployeeService
Provides employee data access.

**Methods**:
```php
interface EmployeeServiceInterface
{
    public function getEmployee(string $employeeId): array
    public function employeeExists(string $employeeId): bool
    public function getEmployeesByDepartment(string $department): array
}
```

#### FAEventDispatcher
Implements event-driven architecture.

**Methods**:
```php
interface EventDispatcherInterface
{
    public function dispatch(object $event): object
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    public function addSubscriber(EventSubscriberInterface $subscriber): void
}
```

### 2.2 Database Functions (pm_db.inc)

Provides procedural database operations for CRUD.

#### Project Functions
- `get_pm_projects($search, $status)` - List projects with filtering
- `get_pm_project($projectId)` - Get single project
- `insert_pm_project($data)` - Create project
- `update_pm_project($projectId, $data)` - Update project
- `delete_pm_project($projectId)` - Delete project

#### Task Functions
- `get_pm_tasks($projectId)` - List tasks
- `get_pm_task($taskId)` - Get single task
- `get_pm_task_count($status)` - Count by status
- `get_pm_overdue_task_count()` - Count overdue tasks
- `insert_pm_task($data)` - Create task
- `update_pm_task($taskId, $data)` - Update task

#### Team Functions
- `get_pm_project_team($projectId)` - List team members
- `assign_employee_to_project($data)` - Add team member
- `remove_employee_from_project($projectId, $employeeId)` - Remove

#### Activity Functions
- `get_pm_recent_activities($limit)` - Get activity log

### 2.3 UI Functions (pm_ui.inc)

Provides presentation logic and helpers.

#### Navigation
- `pm_navigation_menu()` - Main menu tabs

#### Display
- `display_pm_dashboard_stats($stats)` - Dashboard statistics
- `display_pm_stat_cell($label, $value, $type)` - Stat cell with icon
- `display_pm_recent_activities()` - Recent activity list

#### Select Helpers
- `sel_priority($selected)` - Priority dropdown
- `sel_project_status($selected)` - Project status dropdown
- `sel_task_status($selected)` - Task status dropdown
- `sel_project($projects, $selected)` - Project dropdown
- `sel_customer($selected)` - Customer dropdown (from CRM)
- `sel_project_type($selected)` - Project type dropdown

#### Status Helpers
- `get_pm_priority_class($priority)` - CSS class for priority
- `get_pm_status_class($status)` - CSS class for status

---

## 3. Database Schema

### 3.1 Entity Relationship Diagram

```
┌─────────────────┐       ┌─────────────────┐
│   debtors_master│       │    employees    │
│      (FA CRM)   │       │   (FA HRM)      │
└────────┬────────┘       └────────┬────────┘
         │                         │
         │ 1:N                     │ 1:N
         ▼                         ▼
┌─────────────────────────────────────────────────┐
│              fa_pm_projects                     │
│ ┌────────────────────────────────────────────┐ │
│ │ project_id (PK)                            │ │
│ │ name                                      │ │
│ │ description                               │ │
│ │ start_date, end_date                      │ │
│ │ budget                                   │ │
│ │ customer_id (FK) ───────────┐           │ │
│ │ project_manager (FK) ────────┼───────────►│ employee_id
│ │ priority, status              │           │ │
│ │ project_type_id (FK)          │           │ │
│ └────────────────────────────────────────────┘ │
└──────────────────────────┬──────────────────────┘
                         │ 1:N
                         ▼
┌─────────────────────────────────────────────────┐
│               fa_pm_tasks                      │
│ ┌────────────────────────────────────────────┐ │
│ │ task_id (PK)                               │ │
│ │ project_id (FK) ──────────► projects       │ │
│ │ parent_task_id (FK) ────► self            │ │
│ │ name, description                          │ │
│ │ assigned_to (FK) ──────────► employees     │ │
│ │ start_date, end_date                      │ │
│ │ estimated_hours, actual_hours             │ │
│ │ progress                                  │ │
│ │ priority, status                          │ │
│ └────────────────────────────────────────────┘ │
└──────────────────────────┬──────────────────────┘
                         │
                         │ 1:N
                         ▼
┌─────────────────────────────────────────────────┐
│           fa_pm_assignments                     │
│ ┌────────────────────────────────────────────┐ │
│ │ project_id (FK, PK) ─────────► projects     │ │
│ │ employee_id (FK, PK) ───────► employees   │ │
│ │ role                                    │ │
│ │ start_date, end_date                   │ │
│ │ allocation_percentage                  │ │
│ └────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────┘
```

### 3.2 Table Details

#### fa_pm_projects
```sql
CREATE TABLE `@TB_PREF@fa_pm_projects` (
    `project_id` VARCHAR(20) NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `budget` DECIMAL(15,2) DEFAULT 0.00,
    `customer_id` VARCHAR(20) DEFAULT NULL,
    `project_manager` VARCHAR(100) NOT NULL,
    `priority` VARCHAR(20) DEFAULT 'Medium',
    `status` VARCHAR(30) DEFAULT 'Planning',
    `project_type_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`project_id`),
    KEY `idx_status` (`status`),
    KEY `idx_manager` (`project_manager`),
    KEY `idx_customer` (`customer_id`),
    KEY `idx_priority` (`priority`),
    KEY `idx_start_date` (`start_date`),
    CONSTRAINT `fk_pm_customer` FOREIGN KEY (`customer_id`) 
        REFERENCES `@TB_PREF@debtors_master` (`debtor_no`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### fa_pm_tasks
```sql
CREATE TABLE `@TB_PREF@fa_pm_tasks` (
    `task_id` VARCHAR(20) NOT NULL,
    `project_id` VARCHAR(20) NOT NULL,
    `parent_task_id` VARCHAR(20) DEFAULT '',
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT,
    `assigned_to` VARCHAR(100) DEFAULT NULL,
    `start_date` DATE DEFAULT NULL,
    `end_date` DATE DEFAULT NULL,
    `estimated_hours` DECIMAL(10,2) DEFAULT 0.00,
    `actual_hours` DECIMAL(10,2) DEFAULT 0.00,
    `progress` DECIMAL(5,2) DEFAULT 0.00,
    `priority` VARCHAR(20) DEFAULT 'Medium',
    `status` VARCHAR(30) DEFAULT 'Not Started',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`task_id`),
    KEY `idx_project` (`project_id`),
    KEY `idx_parent` (`parent_task_id`),
    KEY `idx_assignee` (`assigned_to`),
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) 
        REFERENCES `@TB_PREF@fa_pm_projects` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### fa_pm_assignments
```sql
CREATE TABLE `@TB_PREF@fa_pm_assignments` (
    `project_id` VARCHAR(20) NOT NULL,
    `employee_id` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'Team Member',
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `allocation_percentage` DECIMAL(5,2) DEFAULT 100.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`project_id`, `employee_id`),
    KEY `idx_employee` (`employee_id`),
    KEY `idx_end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### fa_pm_project_types
```sql
CREATE TABLE `@TB_PREF@fa_pm_project_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### fa_pm_activity_log
```sql
CREATE TABLE `@TB_PREF@fa_pm_activity_log` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `activity_type` VARCHAR(30) NOT NULL,
    `entity_type` VARCHAR(30) NOT NULL,
    `entity_id` VARCHAR(20) NOT NULL,
    `user_id` VARCHAR(100) DEFAULT NULL,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT,
    `old_values` TEXT,
    `new_values` TEXT,
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### fa_pm_files
```sql
CREATE TABLE `@TB_PREF@fa_pm_files` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `entity_type` VARCHAR(30) NOT NULL,
    `entity_id` VARCHAR(20) NOT NULL,
    `file_name` VARCHAR(255) NOT NULL,
    `original_name` VARCHAR(255) NOT NULL,
    `mime_type` VARCHAR(100) DEFAULT 'application/octet-stream',
    `size` INT(11) DEFAULT 0,
    `storage_type` VARCHAR(20) DEFAULT 'local',
    `storage_path` VARCHAR(500) DEFAULT '',
    `uploaded_by` VARCHAR(100) DEFAULT NULL,
    `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `description` TEXT,
    `inactive` TINYINT(1) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_uploaded_by` (`uploaded_by`),
    KEY `idx_inactive` (`inactive`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 4. Integration Patterns

### 4.1 FA Integration

The module integrates with FrontAccounting core:

#### Database Integration
- Uses FA's `db_query()`, `db_fetch_assoc()`, etc.
- Uses `TB_PREF` for table prefix
- Uses `TB_PREF . "debtors_master"` for customers
- Uses `TB_PREF . "employee"` for employees

#### Session Integration
- Uses `$session->check_access()` for permission checks
- Defines permissions in `FA_PM_Module.php`

#### UI Integration
- Uses FA's `page()`, `start_table()`, `end_table()`
- Uses FA's form helpers

### 4.2 Service Integration

The module provides services for external consumption:

```php
// Using the DI container
$container = new PMContainer();
$projectService = $container->get(ProjectServiceInterface::class);
```

### 4.3 Event Integration

PSR-14 event dispatcher for decoupled operations:

```php
$dispatcher = $container->get(EventDispatcherInterface::class);
$dispatcher->dispatch(new ProjectCreatedEvent($projectId));
```

---

## 5. Security Architecture

### 5.1 Permission Model

Defined in FA_PM_Module.php:

| Permission | Description |
|------------|-------------|
| PM_VIEW_PROJECT | View project list |
| PM_MANAGE_PROJECT | Create/edit/delete projects |
| PM_VIEW_TASKS | View tasks |
| PM_MANAGE_TASKS | Create/edit/delete tasks |
| PM_VIEW_TEAM | View team |
| PM_MANAGE_TEAM | Manage team |
| PM_VIEW_REPORTS | View reports |
| PM_ADMIN | Full admin |

### 5.2 Data Validation

- SQL injection prevention via `db_escape()`
- Input sanitization via `htmlspecialchars()`
- Required field validation in business logic

---

## 6. Design Patterns

### 6.1 Patterns Used

| Pattern | Implementation |
|--------|---------------|
| Service Locator | PMContainer |
| Data Access Object | pm_db.inc functions |
| Helper Object | pm_ui.inc functions |
| Event Dispatcher | FAEventDispatcher |
| Factory | Container service creation |

### 6.2 Dependency Management

The PMContainer provides:
- Lazy-loaded services
- Singleton instances for shared services
- Constructor injection for dependent services

---

## 7. Configuration

### 7.1 Module Configuration

Located in pages/settings.php:
- Default project statuses
- Default task statuses
- Notification settings
- Integration settings

### 7.2 Initial Data

Project types inserted on install:
- Software Development
- Infrastructure
- Consulting
- Research
- Marketing
- Event

---

## 8. Deployment

### 8.1 Installation

1. Copy module to `/modules/ksf_FA_ProjectManagement`
2. Activate via FA Modules admin
3. SQL creates tables and inserts initial data
4. Permissions created in FA security

### 8.2 Initialization

_init/init.inc handles:
- Menu registration
- Permission setup
- Version tracking
