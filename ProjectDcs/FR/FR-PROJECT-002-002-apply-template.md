# FR-PROJECT-002-002 - Apply Template to Project

## Functional Requirement

**Module**: ProjectManagement
**Priority**: P0 - Critical
**Status**: Proposed
**Integration**: Hook-based

### Description

Create new project from template, seeding stages and activities.

### Acceptance Criteria

| ID | Criteria | Hook |
|----|----------|------|
| AC-001 | Create project from template | Emit: project_template_applied |
| AC-002 | Copy all stages to project | - |
| AC-003 | Copy all activities to project | - |
| AC-004 | Apply stage constraints (active, date range) | Emit: project_stage_access_check |
| AC-005 | Link project to customer | - |
| AC-006 | Link project to contract (for billing rules) | Query: contract_get_billing_rule |
| AC-007 | Set project owner | - |

### Hooks

```php
// Emit: After template applied to project
hook_invoke_all('project_template_applied', [
    'project_id' => $projectId,
    'template_id' => $templateId,
    'stages' => [
        ['id' => 1, 'code' => 'REQ', 'activities' => [...]],
        ['id' => 2, 'code' => 'DESIGN', 'activities' => [...]],
    ],
    'project_code' => 'PRJ-001',
]);

// Query: Get project current stage
$result = hook_invoke_first('project_get_current_stage', [
    'project_id' => $projectId,
    'date' => '2026-09-07',
]);
// Returns: ['stage_id' => 2, 'stage_code' => 'DESIGN', 'active' => true]

// Query: Get stage activities
$result = hook_invoke_first('project_stage_get_activities', [
    'project_id' => $projectId,
    'stage_id' => 2,
]);
// Returns: ['activities' => [['id' => 5, 'code' => 'ARCH', 'name' => 'Architecture Design']], 'active' => true]
```

### Dependencies

- FR-PROJECT-002-001: Template management
