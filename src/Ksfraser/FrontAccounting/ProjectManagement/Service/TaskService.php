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
 * @BABOK Related: BR-012 (Project Management)
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

    public function projectOptions(): array
    {
        return $this->ref->projectOptions();
    }

    public function userOptions(): array
    {
        return $this->ref->userOptions();
    }

    public function parentOptions(string $projectId, string $excludeTaskId = ''): array
    {
        return $this->repo->parentOptions($projectId, $excludeTaskId);
    }

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

    public function listRowPage(int $page, int $perPage, array $filters = []): array
    {
        return $this->repo->findPage($page, $perPage, $filters);
    }

    public function countRows(array $filters = []): int
    {
        return $this->repo->countAll($filters);
    }

    public function getById(string $id): ?array
    {
        return $this->repo->findById($id);
    }

    // ─── Writes (workflow hooks + activity log) ─────────────────────

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

    public function update(string $id, array $data): void
    {
        $payload = ['record_type' => 'task', 'record_id' => $id, 'data' => $data];
        $this->fireWorkflowHook('task', 'before_save', $payload);

        $this->repo->update($id, (array) ($payload['data'] ?? $data));

        $this->fireWorkflowHook('task', 'edited', $payload);
        $this->fireWorkflowHook('task', 'after_save', $payload);
        $this->log('task', $id, 'updated', (string) ($data['name'] ?? $id));
    }

    public function delete(string $id): void
    {
        $payload = ['record_type' => 'task', 'record_id' => $id];
        $this->fireWorkflowHook('task', 'before_delete', $payload);
        $this->repo->delete($id);
        $this->fireWorkflowHook('task', 'after_delete', $payload);
        $this->log('task', $id, 'deleted');
    }

    private function log(string $entityType, string $entityId, string $action, string $details = ''): void
    {
        if ($this->log !== null) {
            $this->log->log($entityType, $entityId, $action, $details);
        }
    }
}