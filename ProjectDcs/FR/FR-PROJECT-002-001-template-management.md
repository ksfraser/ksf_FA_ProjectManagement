# FR-PROJECT-002-001 - Project Template Management

## Functional Requirement

**Module**: ProjectManagement
**Priority**: P0 - Critical
**Status**: Proposed
**Integration**: Hook-based

### Description

Manage project template library with industry verticals and activity hierarchies.

### Acceptance Criteria

| ID | Criteria | Hook |
|----|----------|------|
| AC-001 | Create template with industry type | Emit: project_template_created |
| AC-002 | Add stages to template | - |
| AC-003 | Add activities to stage | - |
| AC-004 | Clone template as new version | - |
| AC-005 | List templates by industry | - |
| AC-006 | Deactivate template (soft delete) | - |
| AC-007 | Get template with all stages/activities | Query: project_template_get |

### Hooks

```php
// Query: Get template with stages and activities
$result = hook_invoke_first('project_template_get', [
    'template_id' => $templateId,
]);
// Returns: ['template' => [...], 'stages' => [...], 'activities' => [...]]

// Query: List templates
$result = hook_invoke_all('project_template_list', [
    'industry_type' => 'software',
    'active_only' => true,
]);
```

### Dependencies

- FR-PROJECT-002-002: Apply template to project
