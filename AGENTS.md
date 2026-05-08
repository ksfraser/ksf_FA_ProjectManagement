# AGENTS.md - ksf_FA_ProjectManagement#

## Architecture Overview#

This repository implements **Project Management** with OpenProject-style features: progress tracking, versions (quarters), task hierarchies, and integration with Performance module for OKR goals.

### Core Principles#
- **SOLID**: Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation, Dependency Inversion#
- **DRY**: Don't Repeat Yourself - extract reusable logic#
- **TDD**: Test-Driven Development - write tests first#
- **DI**: Dependency Injection - inject dependencies, don't hardcode#
- **SRP**: Single Responsibility Principle - each class has one reason to change#

## Repository Structure#

```
ksf_FA_ProjectManagement/
├── sql/                    # Database schemas (FA TB_PREF tables)
│   ├── fa_pm_projects.sql          # Projects table
│   ├── fa_pm_tasks.sql             # Tasks table (with parent-child)
│   ├── fa_pm_assignments.sql       # Project-team assignments
│   ├── fa_pm_project_types.sql     # Project type definitions
│   ├── fa_pm_activity_log.sql      # Activity tracking
│   ├── fa_pm_files.sql            # File attachments
│   ├── fa_pm_versions.sql         # Versions/quarters (Q1-2026)
│   └── fa_pm_task_progress.sql     # OpenProject-style progress
├── includes/              # FA-specific DB classes
│   ├── pm_db.inc
│   ├── tasks_db.inc
│   ├── assignments_db.inc
│   └── ...
├── pages/                 # UI pages (FA admin)
│   ├── projects.php
│   ├── tasks.php
│   ├── gantt.php
│   └── ...
├── hooks.php              # FA module hooks
├── composer.json
└── ProjectDocs/           # Project documentation
    ├── Requirements.md
    ├── RTM.md            # Requirements Traceability Matrix
    ├── BABOK.md         # Business Analysis Body of Knowledge
    └── UML.md           # UML diagrams
```

## Coding Standards#

### PHP Compatibility#
- **Target**: PHP 7.3+ (with eye to PHP 8.x upgrades)#
- Use `declare(strict_types=1);` at top of all PHP files#

### Progress Tracking Modes (OpenProject-style)#
1. **Work-based**: Progress = Work / (Work + Remaining)#
2. **Status-based**: Progress fixed per status (Draft=0%, In Progress=50%, etc.)#

### Documentation (UML/BABOK)#
```php
/**
 * Create project with tasks
 * 
 * @param array $project_data Project details
 * @param array $tasks Task list with hierarchies
 * @return string Project ID
 * 
 * @UML Note: See ProjectDocs/UML.md - Project creation sequence diagram
 * @BABOK Related: BR-012 Project Management
 */
function create_project_with_tasks($project_data, $tasks) { ... }
```

## Testing Strategy#

### TDD Red-Green-Refactor#
1. **RED**: Write failing test#
2. **GREEN**: Write minimal code to pass#
3. **REFACTOR**: Improve code while keeping tests green#

## Design Patterns Used#

### Composite Pattern (Task Hierarchy)#
- Tasks can have parent-child relationships#
- `parent_task_id` links to `task_id` (self-referencing)#

### Observer Pattern#
- Activity logging for all project/task changes#
- `fa_pm_activity_log` tracks all actions#

### Progress Strategy (OpenProject)#
- `fa_pm_task_progress` supports multiple calculation modes#
- Integrates with Performance module for OKR tracking#

## Version Tagging#

Follow Semantic Versioning (SemVer): `MAJOR.MINOR.PATCH`#

```bash
git tag -a v1.0.0 -m "Initial PM module with projects/tasks"
git push origin v1.0.0
```

## Composer/Packagist#

```json
{
    "name": "ksfraser/ksf_fa_projectmanagement",
    "description": "Project Management for FrontAccounting (OpenProject-style)",
    "type": "frontaccounting-module",
    "require": {
        "php": ">=7.3",
        "ksfraser/ksf_fa_crm": "*",
        "ksfraser/ksf_fa_projectmanagement_core": "*"
    },
    "autoload": {
        "psr-4": {
            "Ksf\\FA\\PM\\": "src/"
        }
    }
}
```

## RTM (Requirements Traceability Matrix)#

See `ProjectDocs/RTM.md` for full traceability:#

| Req ID | Description | Test Case | Code File | Version |
|--------|-------------|-----------|----------|---------|
| REQ-001 | Project Creation | testProjectCreate | sql/fa_pm_projects.sql | v1.0.0 |
| REQ-002 | Task Hierarchy | testTaskParentChild | sql/fa_pm_tasks.sql | v1.0.0 |
| REQ-003 | Progress Tracking | testProgressCalc | sql/fa_pm_task_progress.sql | v1.1.0 |
| REQ-004 | Gantt Chart | testGanttDisplay | pages/gantt.php | v1.2.0 |

## BABOK Alignment#

See `ProjectDocs/BABOK.md` for business analysis alignment:#

### Business Requirements (BABOK)#
- **BR-012**: Project Management - Full project lifecycle#
- **BR-013**: Resource Allocation - Team assignments with roles#
- **BR-014**: Progress Monitoring - OpenProject-style tracking#
- **BR-015**: Integration - Link PM tasks to OKR goals#

## UML Documentation#

See `ProjectDocs/UML.md` for:#
- Project-task hierarchy diagram#
- Gantt chart component diagram#
- Progress calculation state diagram#

## Dependencies#

- **ksf_FA_ProjectManagement_Core** (business logic)#
- **ksf_FA_CRM** (customer contacts for projects)#
- **ksf_FA_Performance** (OKR goal linkage - optional)#
- **FrontAccounting 2.4+** (FA core)#
