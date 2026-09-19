<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\Common\Traits\WorkflowHooksTrait;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ReferenceRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ActivityLogRepository;

/**
 * ProjectService — business logic + workflow hooks for the Projects tab.
 *
 * The per-row lifecycle hooks (SuiteCRM-style) fire through WorkflowHooksTrait
 * as `pm_project_before_save`, `pm_project_edited`, `pm_project_after_save`,
 * `pm_project_before_delete`, `pm_project_after_delete`, etc., via FA's
 * hook_invoke_all. They are wired ONTO the DAO layer (ProjectRepository, built
 * on DbConnectionInterface) — the cross-module porting direction from AGENTS.md.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (pre/post workflow hooks on the DAO layer)
 * @BABOK Related: BR-012 (Project Management)
 */
class ProjectService
{
    use WorkflowHooksTrait;

    /** @var ProjectRepository */
    private $repo;

    /** @var ReferenceRepository */
    private $ref;

    /** @var ActivityLogRepository|null */
    private $log;

    public function __construct(
        ?ProjectRepository $repo = null,
        ?ReferenceRepository $ref = null,
        ?ActivityLogRepository $log = null
    ) {
        $this->repo = $repo ?? new ProjectRepository();
        $this->ref  = $ref  ?? new ReferenceRepository();
        $this->log  = $log ?? new ActivityLogRepository();
        $this->registerWorkflowType('project', 'pm_project');
    }

    // ─── Reference / DDL ────────────────────────────────────────────

    public function customerOptions(): array
    {
        return $this->ref->customerOptions();
    }

    public function userOptions(): array
    {
        return $this->ref->userOptions();
    }

    public function projectTypeOptions(): array
    {
        return $this->ref->projectTypeOptions();
    }

    public static function statusOptions(): array
    {
        return [
            'Planning'   => 'Planning',
            'Active'     => 'Active',
            'On Hold'    => 'On Hold',
            'Completed'  => 'Completed',
            'Cancelled'  => 'Cancelled',
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
     * Field metadata for the Projects tab (FR-006-007 schema).
     *
     * @return array<string, mixed>
     * @since 1.0.0
     */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'      => 'project',
            'table'       => '0_fa_pm_projects',
            'label'       => 'Project',
            'labelPlural' => 'Projects',
            'hookPrefix'  => 'Project',
            'pk'          => 'project_id',
            'fields'      => [
                'project_id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
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
                'start_date' => [
                    'label' => 'Start Date', 'type' => 'date', 'default' => date('Y-m-d'),
                    'showInTable' => true, 'showInForm' => true,
                ],
                'end_date' => [
                    'label' => 'End Date', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'budget' => [
                    'label' => 'Budget', 'type' => 'number', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'customer_id' => [
                    'label' => 'Customer', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'project_manager' => [
                    'label' => 'Manager', 'type' => 'select', 'default' => '',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'priority' => [
                    'label' => 'Priority', 'type' => 'select', 'default' => 'Medium',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'status' => [
                    'label' => 'Status', 'type' => 'select', 'default' => 'Planning',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'project_type_id' => [
                    'label' => 'Type', 'type' => 'select',
                    'showInTable' => false, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'start_date DESC, project_id DESC'],
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
        $payload = ['record_type' => 'project', 'data' => $data];
        $this->fireWorkflowHook('project', 'before_save', $payload);

        $id = $this->repo->save((array) ($payload['data'] ?? $data));

        $payload['record_id'] = $id;
        $this->fireWorkflowHook('project', 'new', $payload);
        $this->fireWorkflowHook('project', 'after_save', $payload);
        $this->log('project', $id, 'created', (string) ($data['name'] ?? $id));

        return $id;
    }

    public function update(string $id, array $data): void
    {
        $payload = ['record_type' => 'project', 'record_id' => $id, 'data' => $data];
        $this->fireWorkflowHook('project', 'before_save', $payload);

        $this->repo->update($id, (array) ($payload['data'] ?? $data));

        $this->fireWorkflowHook('project', 'edited', $payload);
        $this->fireWorkflowHook('project', 'after_save', $payload);
        $this->log('project', $id, 'updated', (string) ($data['name'] ?? $id));
    }

    public function delete(string $id): void
    {
        $payload = ['record_type' => 'project', 'record_id' => $id];
        $this->fireWorkflowHook('project', 'before_delete', $payload);
        $this->repo->delete($id);
        $this->fireWorkflowHook('project', 'after_delete', $payload);
        $this->log('project', $id, 'deleted');
    }

    private function log(string $entityType, string $entityId, string $action, string $details = ''): void
    {
        if ($this->log !== null) {
            $this->log->log($entityType, $entityId, $action, $details);
        }
    }
}