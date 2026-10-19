<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\Common\Traits\WorkflowHooksTrait;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ReferenceRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ActivityLogRepository;

/**
 * TaskService — business logic + workflow hooks for the Tasks tab.
 *
 * Fires lifecycle hooks as `pm_task_before_save`, `pm_task_new`/`pm_task_edited`,
 * `pm_task_after_save`, `pm_task_before_delete`, `pm_task_after_delete` via FA's
 * hook_invoke_all, wired onto the DAO layer (TaskRepository on DbConnectionInterface).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (pre/post workflow hooks on the DAO layer)
 * @BABOK Related: FR-006-007, N/A — the field metadata it serves, plus: no requirement
 *   defined in this repo for task management; that set (FR-PM-002) lives in
 *   ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`.
 */
class TaskService
{
    use WorkflowHooksTrait;

    /** @var TaskRepository */
    private $repo;

    /** @var ReferenceRepository */
    private $ref;

    /** @var ActivityLogRepository|null */
    private $log;

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function __construct(
        ?TaskRepository $repo = null,
        ?ReferenceRepository $ref = null,
        ?ActivityLogRepository $log = null
    ) {
        $this->repo = $repo ?? new TaskRepository();
        $this->ref  = $ref  ?? new ReferenceRepository();
        $this->log  = $log ?? new ActivityLogRepository();
        $this->registerWorkflowType('task', 'pm_task');
    }

    // ─── Reference / DDL ────────────────────────────────────────────

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — FK select options
     */
    public function projectOptions(): array
    {
        return $this->ref->projectOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — FK select options
     */
    public function userOptions(): array
    {
        return $this->ref->userOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — hierarchical parent select options
     */
    public function parentOptions(string $projectId, string $excludeTaskId = ''): array
    {
        return $this->repo->parentOptions($projectId, $excludeTaskId);
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — status options
     */
    public static function statusOptions(): array
    {
        return [
            'Not Started' => 'Not Started',
            'In Progress' => 'In Progress',
            'On Hold'     => 'On Hold',
            'Completed'   => 'Completed',
            'Cancelled'   => 'Cancelled',
        ];
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — priority options
     */
    public static function priorityOptions(): array
    {
        return [
            'Low'      => 'Low',
            'Medium'   => 'Medium',
            'High'     => 'High',
            'Critical' => 'Critical',
        ];
    }

    /**
     * Field metadata for the Tasks tab (FR-006-007 schema).
     *
     * @return array<string, mixed>
     * @since 1.0.0
      *
 * @BABOK Related: FR-006-007 — field-metadata structure — the field metadata the tab surface renders
 */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'      => 'task',
            'table'       => '0_fa_pm_tasks',
            'label'       => 'Task',
            'labelPlural' => 'Tasks',
            'hookPrefix'  => 'Task',
            'pk'          => 'task_id',
            'fields'      => [
                'task_id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'project_id' => [
                    'label' => 'Project', 'type' => 'select',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'parent_task_id' => [
                    'label' => 'Parent Task', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'name' => [
                    'label' => 'Name', 'type' => 'text',
                    'required' => true, 'max' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'description' => [
                    'label' => 'Description', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'assigned_to' => [
                    'label' => 'Assigned To', 'type' => 'select',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'start_date' => [
                    'label' => 'Start Date', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'end_date' => [
                    'label' => 'End Date', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'estimated_hours' => [
                    'label' => 'Est. Hours', 'type' => 'number', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'actual_hours' => [
                    'label' => 'Actual Hours', 'type' => 'number', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'progress' => [
                    'label' => 'Progress %', 'type' => 'number', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'priority' => [
                    'label' => 'Priority', 'type' => 'select', 'default' => 'Medium',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'Not Started',
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'project_id, task_id DESC'],
        ];
    }

    // ─── Reads ──────────────────────────────────────────────────────

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function listRowPage(int $page, int $perPage, array $filters = []): array
    {
        return $this->repo->findPage($page, $perPage, $filters);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function countRows(array $filters = []): int
    {
        return $this->repo->countAll($filters);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function getById(string $id): ?array
    {
        return $this->repo->findById($id);
    }

    // ─── Writes (workflow hooks + activity log) ─────────────────────

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — create, wrapped in the pre/post CRUD workflow hooks
     */
    public function create(array $data): string
    {
        $payload = ['record_type' => 'task', 'data' => $data];
        $this->fireWorkflowHook('task', 'before_save', $payload);

        $id = $this->repo->save((array) ($payload['data'] ?? $data));

        $payload['record_id'] = $id;
        $this->fireWorkflowHook('task', 'new', $payload);
        $this->fireWorkflowHook('task', 'after_save', $payload);
        $this->log('task', $id, 'created', (string) ($data['name'] ?? $id));

        return $id;
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — update, wrapped in the pre/post CRUD workflow hooks
     */
    public function update(string $id, array $data): void
    {
        $payload = ['record_type' => 'task', 'record_id' => $id, 'data' => $data];
        $this->fireWorkflowHook('task', 'before_save', $payload);

        $this->repo->update($id, (array) ($payload['data'] ?? $data));

        $this->fireWorkflowHook('task', 'edited', $payload);
        $this->fireWorkflowHook('task', 'after_save', $payload);
        $this->log('task', $id, 'updated', (string) ($data['name'] ?? $id));
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; task management is specified logic-repo-side as FR-PM-002 (see the class docblock) — delete, wrapped in the pre/post CRUD workflow hooks
     */
    public function delete(string $id): void
    {
        $payload = ['record_type' => 'task', 'record_id' => $id];
        $this->fireWorkflowHook('task', 'before_delete', $payload);
        $this->repo->delete($id);
        $this->fireWorkflowHook('task', 'after_delete', $payload);
        $this->log('task', $id, 'deleted');
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo for the activity log (see the class docblock) — records the lifecycle action
     */
    private function log(string $entityType, string $entityId, string $action, string $details = ''): void
    {
        if ($this->log !== null) {
            $this->log->log($entityType, $entityId, $action, $details);
        }
    }
}