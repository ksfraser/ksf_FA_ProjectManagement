<!-- Repo-specific appendix to the shared AGENTS.md. Generic conventions live in AGENTS_ARCH.md (hardlinked). -->

```markdown
# AGENTS.local.md — ksf_FA_ProjectManagement
## Overview
**FA Module** for Project Management with OpenProject-style features: progress tracking, versions (quarters), task hierarchies, and integration with Performance module for OKR goals.
## Repository Structure
```
ksf_FA_ProjectManagement/
├── sql/
│   ├── fa_pm_projects.sql          # Projects table
│   ├── fa_pm_tasks.sql             # Tasks table (with parent-child)
│   ├── fa_pm_assignments.sql       # Project-team assignments
│   ├── fa_pm_project_types.sql     # Project type definitions
│   ├── fa_pm_activity_log.sql      # Activity tracking
│   ├── fa_pm_files.sql             # File attachments
│   ├── fa_pm_versions.sql          # Versions/quarters (Q1-2026)
│   └── fa_pm_task_progress.sql     # OpenProject-style progress
├── includes/
│   ├── pm_db.inc
│   ├── tasks_db.inc
│   ├── assignments_db.inc
│   └── ...
├── pages/
│   ├── projects.php
│   ├── tasks.php
│   ├── gantt.php
│   └── ...
├── hooks.php
├── composer.json
└── ProjectDocs/
    ├── Requirements.md
    ├── RTM.md
    ├── BABOK.md
    └── UML.md
```
## Progress Tracking Modes (OpenProject-style)
1. **Work-based**: Progress = Work / (Work + Remaining)
2. **Status-based**: Progress fixed per status (Draft=0%, In Progress=50%, etc.)
## Documentation (UML/BABOK)
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
## Design Patterns Used
### Composite Pattern (Task Hierarchy)
- Tasks can have parent-child relationships
- `parent_task_id` links to `task_id` (self-referencing)
### Observer Pattern
- Activity logging for all project/task changes
- `fa_pm_activity_log` tracks all actions
### Progress Strategy (OpenProject)
- `fa_pm_task_progress` supports multiple calculation modes
- Integrates with Performance module for OKR tracking
## Composer/Packagist
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
## RTM (Requirements Traceability Matrix)
See `ProjectDocs/RTM.md` for full traceability:
| Req ID | Description | Test Case | Code File | Version |
|--------|-------------|-----------|----------|---------|
| REQ-001 | Project Creation | testProjectCreate | sql/fa_pm_projects.sql | v1.0.0 |
| REQ-002 | Task Hierarchy | testTaskParentChild | sql/fa_pm_tasks.sql | v1.0.0 |
| REQ-003 | Progress Tracking | testProgressCalc | sql/fa_pm_task_progress.sql | v1.1.0 |
| REQ-004 | Gantt Chart | testGanttDisplay | pages/gantt.php | v1.2.0 |
## BABOK Alignment
See `ProjectDocs/BABOK.md` for business analysis alignment:
- **BR-012**: Project Management - Full project lifecycle
- **BR-013**: Resource Allocation - Team assignments with roles
- **BR-014**: Progress Monitoring - OpenProject-style tracking
- **BR-015**: Integration - Link PM tasks to OKR goals
## UML Documentation
See `ProjectDocs/UML.md` for:
- Project-task hierarchy diagram
- Gantt chart component diagram
- Progress calculation state diagram
## Dependencies
- **ksf_FA_ProjectManagement_Core** (business logic)
- **ksf_FA_CRM** (customer contacts for projects)
- **ksf_FA_Performance** (OKR goal linkage - optional)
- **FrontAccounting 2.4+** (FA core)
```
