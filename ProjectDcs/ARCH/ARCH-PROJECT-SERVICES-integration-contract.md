# Shared Events Document - Cross-Module Integration

## Hook Events Registry

### Project Service Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `project_template_applied` | ProjectManagement | Timesheets, TravelExpense, Marketing | DTO: project_id, stages[] | Emit |
| `project_activity_status_changed` | ProjectManagement | All | DTO: activity status | Emit |
| `project_stage_access_check` | ProjectManagement | Timesheets, TravelExpense | Query/Response | Bidirectional |
| `project_get_current_stage` | ProjectManagement | Expense (constraint check) | Query | Bidirectional |
| `project_stage_get_activities` | ProjectManagement | Timesheets, TravelExpense | Query | Bidirectional |

### Expense Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `expense_line_added` | TravelExpense | - | DTO: line details | Emit |
| `expense_submitted` | TravelExpense | Teams (approval chain) | DTO: report_summary | Emit |
| `expense_approved` | TravelExpense (on approval) | Contract Billing | DTO: approved_items | Emit |
| `expense_reimbursed` | TravelExpense (on reimbursement) | Payroll/GL | DTO: payment_details | Emit |
| `expense_get_billing_rule` | Contract/Project | TravelExpense | Query: contract rules | Bidirectional |

### Time Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `timesheet_submitted` | Timesheets | Teams (approval) | DTO: timesheet_summary | Emit |
| `timesheet_approved` | Timesheets (on approval) | Contract Billing, Payroll | DTO: approved_items | Emit |
| `time_get_billing_rule` | Contract/Project | Timesheets | Query: rules | Bidirectional |
| `project_activity_validate` | ProjectManagement | Timesheets | Query: valid/invalid | Bidirectional |
| `timesheet_export_payroll` | Timesheets | Payroll | DTO: hours/rates | Emit |

### Approval Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `approval_request` | Teams | Expense, Timesheets | DTO: approval_request | Bidirectional |
| `approval_approve` | Teams | All | DTO: approval_result | Emit |
| `approval_reject` | Teams | All | DTO: rejection_reason | Emit |
| `approval_escalate` | Teams (timeout) | All | DTO: escalation_details | Emit |
| `approval_delegate` | Teams | All | DTO: delegation_info | Emit |

### Billing Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `contract_get_billing_rule` | Sales/Contract | Expense, Timesheets | Query: billing_rules | Bidirectional |
| `expense_approval_billing_applied` | TravelExpense (on approval) | Contract Billing | DTO: billable_items | Emit |
| `expense_billing_batch_created` | Contract Billing | Sales/AR | DTO: batch_items | Emit |

### Payroll Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `payroll_activity_code_created` | Payroll (from approved timesheet) | Timesheets | DTO: payroll_code | Emit |
| `payroll_node_processed` | Payroll | All | DTO: payroll_details | Emit |

### Marketing Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `marketing_project_template_applied` | Marketing | ProjectManagement | DTO: marketing_template | Emit |

### Notification Events

| Hook | Module Origin | Consumers | Data | Direction |
|------|---------------|-----------|------|-----------|
| `approval_notify` | Teams | Email Module | DTO: notification_details | Emit |

---

## Cross-Domain Relationships

### Project → Expense → Contract → Billing → Invoice

```
Project (from Template)
    │
    ├── Activity Codes (layered: stage → activity)
    │
    ├── Contract Billing Rules (cost, cost_plus, fixed_rate)
    │       │ (hook: contract_get_billing_rule)
    │       ▼
Expense Entry (project_id + activity_id + category)
    │
    ├── Activity Validation (hook: project_activity_validate)
    │
    ├── Billing Rule Applied (hook: expense_get_billing_rule)
    │
    ├── Submit (hook: expense_submitted)
    │       │
    │       ▼
    ├── Approval (hook: approval_request → Teams module)
    │       │
    │       ├── Approve (hook: expense_approved)
    │       │
    │       ▼
    ├── Contract Billing Module (hook: expense_approval_billing_applied)
    │       │
    │       ├── Direct Delivery Created (table: billing_batch_items)
    │       │
    │       ▼
    ├── AR Batch Processing (group by customer/project/contract)
    │       │
    │       ▼
    └── Invoice Generation (hook: invoice_create or direct delivery invoiced)
```

### Project → Timesheet → Approval → Payroll → GL

```
Project (from Template)
    │
    ├── Activity Codes
    │
    Timesheet Entry (project_id + activity_id + hours + date)
    │
    ├── Activity Validation (hook: project_activity_validate)
    │
    ├── Billing Rule Applied (hook: time_get_billing_rule)
    │
    ├── Submit (hook: timesheet_submitted)
    │       │
    │       ▼
    ├── Approval (hook: approval_request)
    │       │
    │       ▼
    ├── Timesheet Approved (hook: timesheet_approved)
    │       │
    │       ▼
    ├── Payroll Export (hook: timesheet_export_payroll)
    │       │
    │       ├── Payroll Activity Codes Generated
    │       │
    │       ▼
    └── Payroll Processing (calculated OT, hourly rate, total cost)
            │
            ▼
        GL Entries Created (hook: gl_entry_create)
```

### Expense + Time Correlation (+/- 1 Day)

When time or expense entries are created for project activities, check proximity:

```php
// In expense entry:
hook_invoke_first('expense_check_time_correlation', [
    'expense_report_id' => $reportId,
    'expense_date' => '2026-09-07',
    'project_id' => $projectId,
    'activity_id' => $activityId,
]);
// Returns: ['related_time_entries' => [$entry1, $entry2], 'time_range' => '2026-09-06 to 2026-09-08']

// In timesheet entry:
hook_invoke_first('time_check_expense_correlation', [
    'timesheet_id' => $timesheetId,
    'entry_date' => '2026-09-07',
    'project_id' => $projectId,
    'activity_id' => $activityId,
]);
// Returns: ['related_expenses' => [$expense1], 'expense_range' => '2026-09-06 to 2026-09-08']
```

### Auto-Approval Workflows

```php
// Auto-approve small expenses
hook_invoke_first('expense_check_auto_approve', [
    'expense_report_id' => $reportId,
    'total_amount' => $total,
    'category' => 'meal',
    'project_id' => $projectId,
]);
// Returns: ['auto_approve' => true, 'reason' => 'Under threshold: $50']

// Auto-approve timesheet within range
hook_invoke_first('timesheet_check_auto_approve', [
    'timesheet_id' => $timesheetId,
    'regular_hours' => 40,
    'overtime_hours' => 0,
    'total_hours' => 40,
]);
// Returns: ['auto_approve' => true, 'reason' => 'Within normal range']
```

### Status Change Tracking

Both expense reports and timesheets have full status tracking:

```
Status: draft → submitted → pending_approval → approved/rejected/denied → returned (editing) → resubmitted
             └──→ returned (for editing) ──> resubmitted
```

Status change comments stored in xref table:
```sql
CREATE TABLE `0_approval_step_comments` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `approval_chain_id` INT UNSIGNED NOT NULL,
  `step` INT UNSIGNED NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `status_change` VARCHAR(20) NOT NULL,
  `comment` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_chain_step` (`approval_chain_id`, `step`)
);
```

---

## Module Integration Dependencies

### ProjectManagement
- Depends: ksf_FA_Teams (approval chain)
- Consumes Hook: contract_get_billing_rule (from Sales)
- Emits Hook: project_template_applied, project_stage_access_check

### Timesheets
- Depends: ksf_FA_ProjectManagement (activity codes, validation)
- Depends: ksf_FA_Teams (approval chain)
- Consumes Hook: project_activity_validate, project_get_current_stage
- Emits Hook: timesheet_submitted, timesheet_approved, timesheet_export_payroll

### TravelExpense
- Depends: ksf_FA_ProjectManagement (project/stage/activity linkage)
- Depends: ksf_FA_Teams (approval chain)
- Depends: ksf_FA_RBAC (permissions)
- Depends: ksf_FA_Common (EncryptedFields for receipt storage)
- Consumes Hook: project_stage_access_check, project_stage_get_activities, contract_get_billing_rule
- Emits Hook: expense_submitted, expense_approved, expense_rejected, expense_get_billing_rule

### Marketing
- Depends: ksf_FA_ProjectManagement (template applied)
- Emits Hook: marketing_project_template_applied, project_template_applied

### Sales
- Depends: ksf_FA_Common (DB adapter)
- Consumes Hook: expense_approval_billing_applied (for direct delivery creation)
- Emits Hook: contract_get_billing_rule, contract_get_opportunities

### Teams (Approval Engine as Package)
- Consumes: All approval_request hooks
- Queries: orgchart_* hooks
- Emits: approval_approve, approval_reject, approval_escalate, approval_notify

---

*This document serves as the integration contract for all Project Services modules.*
*All module integrations MUST use hook interfaces, not direct class instantiation.*
