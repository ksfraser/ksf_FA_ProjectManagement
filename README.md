# FrontAccounting Project Management Module

**ksf_FA_ProjectManagement** - FrontAccounting module wrapper for `ksfraser/ksf-project-management`.

## Quick Start

```bash
# 1. Install the composer package in your FA installation
composer require ksfraser/ksf-project-management

# 2. Copy this module to FA modules directory
cp -r ksf_FA_ProjectManagement /path/to/frontaccounting/modules/

# 3. Activate via FA Admin → Setup → Modules
```

## Features

- Full CRUD for Projects, Tasks, and Team Assignments
- Integration with FrontAccounting customer management
- EmployeeManagement module integration
- Dashboard with project statistics
- Reporting and analytics
- Event-driven architecture

## Structure

```
ksf_FA_ProjectManagement/
├── hooks.php          # FA lifecycle hooks
├── FA_PM_Module.php   # Module class + permissions
├── pm.php             # API controller
├── _init/             # Module initialization
├── includes/          # DB functions, UI helpers, DI container
├── pages/             # UI pages (dashboard, projects, tasks, etc.)
├── sql/               # Database schema
└── doc/               # ProjectDocuments
```

## Dependencies

- FrontAccounting 2.4.0+
- PHP 8.0+
- `ksfraser/ksf-project-management` (the core PM library)

## Database Tables

- `fa_pm_projects` - Project records
- `fa_pm_tasks` - Task records with hierarchy
- `fa_pm_assignments` - Employee-project assignments
- `fa_pm_project_types` - Project type classifications
- `fa_pm_activity_log` - Audit trail

## Permissions

| Permission | Description |
|------------|-------------|
| PM_VIEW_PROJECT | View projects |
| PM_MANAGE_PROJECT | Create/edit projects |
| PM_VIEW_TASKS | View tasks |
| PM_MANAGE_TASKS | Create/edit tasks |
| PM_VIEW_TEAM | View team |
| PM_MANAGE_TEAM | Manage team |
| PM_VIEW_REPORTS | View reports |
| PM_ADMIN | Full admin access |

## Integration

This module integrates with:
- **FA_CRM** - Customer linking from projects
- **EmployeeManagement** - Employee data
- **ksfraser/ksf-project-management** - Core PM logic package

## License

GPL-3.0