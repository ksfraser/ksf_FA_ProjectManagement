# ARCH-PROJECT-002 - Project Services Architecture

## Overview

Unified project services architecture integrating:
- Project Templates & Activity Codes (BR-PROJECT-002)
- Expense Tracking (BR-EXPENSE-001)
- Time Tracking (BR-TIME-001)
- Approval Chain Engine (BR-APPROVAL-001)

## System Context

```
┌─────────────────────────────────────────────────────────────────┐
│                     User Interface                              │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │ Project Tab │  │Timesheet UI│  │ Expense Report UI       │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   ksf_FA_ProjectManagement                     │
│  ┌───────────────┐  ┌───────────────┐  ┌───────────────────┐   │
│  │TemplateService│  │StageService   │  │ActivityService    │   │
│  └───────────────┘  └───────────────┘  └───────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                        Hook Infrastructure                        │
│                                                                  │
│  hook_invoke_all('project_activity_changed', $data)            │
│  hook_invoke_all('expense_submitted', $data)                   │
│  hook_invoke_all('timesheet_approved', $data)                   │
│  hook_invoke_all('approval_request', $data)                     │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ksf_FA_Timesheets│  │ksf_FA_TravelExp│  │ ksf_FA_Teams    │
│                 │  │                 │  │                 │
│ - Time entries  │  │ - Expense lines │  │ - Approval chain│
│ - Activity codes│  │ - Billing rules │  │ - Org chart     │
│ - Overtime calc │  │ - Receipts      │  │ - Delegation    │
└─────────────────┘  └─────────────────┘  └─────────────────┘
          │                   │                   │
          └───────────────────┼───────────────────┘
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                         FA Core Modules                           │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐   │
│  │ GL Entries   │  │ Dimensions   │  │  Invoice/Billing    │   │
│  └──────────────┘  └──────────────┘  └──────────────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

## Database Schema

### Project Templates

```sql
CREATE TABLE `0_project_templates` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `template_code` VARCHAR(30) NOT NULL,
  `template_name` VARCHAR(100) NOT NULL,
  `industry_type` ENUM('software','construction','sales','manufacturing','generic') NOT NULL,
  `description` TEXT,
  `version` INT UNSIGNED NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_template_code` (`template_code`)
);

CREATE TABLE `0_project_template_stages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `template_id` INT UNSIGNED NOT NULL,
  `stage_code` VARCHAR(30) NOT NULL,
  `stage_name` VARCHAR(100) NOT NULL,
  `sequence` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `active_from` DATE NULL,
  `active_to` DATE NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_template` (`template_id`)
);

CREATE TABLE `0_project_template_activities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `stage_id` INT UNSIGNED NOT NULL,
  `activity_code` VARCHAR(30) NOT NULL,
  `activity_name` VARCHAR(100) NOT NULL,
  `sequence` INT UNSIGNED NOT NULL,
  `default_gl_code` VARCHAR(30),
  `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  KEY `idx_stage` (`stage_id`)
);

-- Project instance (from template)
CREATE TABLE `0_projects` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_code` VARCHAR(30) NOT NULL,
  `project_name` VARCHAR(200) NOT NULL,
  `customer_id` INT UNSIGNED,
  `contract_id` INT UNSIGNED,
  `template_id` INT UNSIGNED,
  `owner_user_id` INT UNSIGNED NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE,
  `status` ENUM('planned','active','on_hold','completed','cancelled') NOT NULL DEFAULT 'planned',
  `total_budget` DECIMAL(15,2),
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_project_code` (`project_code`)
);

CREATE TABLE `0_project_stages` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_id` INT UNSIGNED NOT NULL,
  `template_stage_id` INT UNSIGNED,
  `stage_code` VARCHAR(30) NOT NULL,
  `stage_name` VARCHAR(100) NOT NULL,
  `sequence` INT UNSIGNED NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `active_from` DATE NULL,
  `active_to` DATE NULL,
  `actual_start` DATE,
  `actual_end` DATE,
  KEY `idx_project` (`project_id`)
);

CREATE TABLE `0_project_activities` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `project_stage_id` INT UNSIGNED NOT NULL,
  `activity_code` VARCHAR(30) NOT NULL,
  `activity_name` VARCHAR(100) NOT NULL,
  `sequence` INT UNSIGNED NOT NULL,
  `status` ENUM('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
  `billing_rule` ENUM('cost','cost_plus','fixed_rate','not_billable') NOT NULL DEFAULT 'cost',
  `billing_margin` DECIMAL(5,2) COMMENT 'Percentage for cost_plus',
  `billing_rate` DECIMAL(15,2) COMMENT 'Fixed rate per unit',
  KEY `idx_stage` (`project_stage_id`)
);
```

### Expense Integration

```sql
CREATE TABLE `0_expense_reports` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `report_number` VARCHAR(30) NOT NULL,
  `employee_id` INT UNSIGNED NOT NULL,
  `project_id` INT UNSIGNED,
  `submitted_date` DATE NOT NULL,
  `total_amount` DECIMAL(15,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'USD',
  `status` ENUM('draft','submitted','pending_approval','approved','rejected','reimbursed') NOT NULL DEFAULT 'draft',
  `approval_chain_id` INT UNSIGNED,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_report_number` (`report_number`)
);

CREATE TABLE `0_expense_lines` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `expense_report_id` INT UNSIGNED NOT NULL,
  `expense_date` DATE NOT NULL,
  `category` VARCHAR(30) NOT NULL,
  `description` VARCHAR(255),
  `amount` DECIMAL(15,2) NOT NULL,
  `currency` CHAR(3) NOT NULL DEFAULT 'USD',
  `exchange_rate` DECIMAL(15,6) NOT NULL DEFAULT 1.000000,
  `amount_base` DECIMAL(15,2) NOT NULL COMMENT 'In base currency',
  `gl_code` VARCHAR(30) NOT NULL,
  `project_id` INT UNSIGNED,
  `project_stage_id` INT UNSIGNED,
  `project_activity_id` INT UNSIGNED,
  `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
  `receipt_path` VARCHAR(500),
  `adjustment_type` VARCHAR(10) COMMENT '+amount, -amount, +%, -%, =amount',
  `adjustment_value` DECIMAL(15,2),
  KEY `idx_report` (`expense_report_id`)
);
```

### Time Tracking Integration

```sql
CREATE TABLE `0_timesheets` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT UNSIGNED NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `status` ENUM('draft','submitted','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft',
  `regular_hours` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `overtime_hours` DECIMAL(8,2) NOT NULL DEFAULT 0,
  `approval_chain_id` INT UNSIGNED,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_employee_period` (`employee_id`, `period_start`)
);

CREATE TABLE `0_time_entries` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `timesheet_id` INT UNSIGNED NOT NULL,
  `entry_date` DATE NOT NULL,
  `hours` DECIMAL(6,2) NOT NULL,
  `hour_type` ENUM('regular','overtime','double_time') NOT NULL DEFAULT 'regular',
  `project_id` INT UNSIGNED,
  `project_stage_id` INT UNSIGNED,
  `project_activity_id` INT UNSIGNED,
  `description` VARCHAR(255),
  `billing_rule` ENUM('cost','cost_plus','fixed_rate','not_billable') NOT NULL DEFAULT 'cost',
  `billing_rate` DECIMAL(15,2),
  `is_billable` TINYINT(1) NOT NULL DEFAULT 1,
  KEY `idx_timesheet` (`timesheet_id`)
);
```

### Approval Chain

```sql
CREATE TABLE `0_approval_chains` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chain_name` VARCHAR(100) NOT NULL,
  `document_type` VARCHAR(50) NOT NULL,
  `document_id` INT UNSIGNED NOT NULL,
  `submitter_id` INT UNSIGNED NOT NULL,
  `current_step` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `submitted_at` DATETIME NOT NULL,
  `completed_at` DATETIME,
  UNIQUE KEY `uk_document` (`document_type`, `document_id`)
);

CREATE TABLE `0_approval_steps` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `chain_id` INT UNSIGNED NOT NULL,
  `step` INT UNSIGNED NOT NULL,
  `approver_type` ENUM('project_manager','department_manager','team_lead','specific_user','role','delegation') NOT NULL,
  `approver_id` INT UNSIGNED,
  `approver_role` VARCHAR(50),
  `timeout_hours` INT UNSIGNED,
  `escalate_to_step` INT UNSIGNED,
  `status` ENUM('pending','approved','rejected','escalated','skipped') NOT NULL DEFAULT 'pending',
  `assigned_to` INT UNSIGNED,
  `completed_at` DATETIME,
  `comments` TEXT,
  KEY `idx_chain` (`chain_id`)
);

CREATE TABLE `0_approval_delegations` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `delegator_id` INT UNSIGNED NOT NULL,
  `delegate_id` INT UNSIGNED NOT NULL,
  `valid_from` DATE NOT NULL,
  `valid_until` DATE NOT NULL,
  `document_types` JSON COMMENT '["expense_report","time_sheet"]',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  KEY `idx_delegator` (`delegator_id`)
);
```

## Hook Contract Summary

| Hook Name | Direction | Purpose |
|-----------|-----------|---------|
| `project_template_applied` | Emit | Notify when project created from template |
| `project_activity_status_changed` | Emit | Activity status change notification |
| `project_stage_access_check` | Query | Check if stage is accessible |
| `project_get_current_stage` | Query | Get current active stage |
| `project_stage_get_activities` | Query | Get activities for stage |
| `expense_submitted` | Emit | Expense report submitted |
| `expense_approved` | Emit | Expense report approved |
| `expense_rejected` | Emit | Expense report rejected |
| `expense_reimbursed` | Emit | Expense reimbursed |
| `expense_get_billing_rule` | Query | Get billing rule for expense |
| `timesheet_submitted` | Emit | Timesheet submitted |
| `timesheet_approved` | Emit | Timesheet approved |
| `time_get_billing_rule` | Query | Get billing rule for time entry |
| `project_activity_validate` | Query | Validate activity access |
| `timesheet_export_payroll` | Emit | Export timesheet to payroll |
| `approval_request` | Emit | Request approval |
| `approval_approve` | Emit | Approve document |
| `approval_reject` | Emit | Reject document |
| `approval_escalate` | Emit | Escalate timeout |
| `approval_delegate` | Emit | Delegate approval |
| `approval_get_next_approver` | Query | Get next approver |
| `approval_can_approve` | Query | Check approval permission |
| `approval_check_delegation` | Query | Check delegation |
| `approval_notify` | Emit | Send notification |
| `orgchart_get_manager` | Query | Get user's manager |
| `orgchart_get_team` | Query | Get team members |
| `orgchart_get_department_head` | Query | Get department head |
