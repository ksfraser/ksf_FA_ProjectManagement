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
 * @BABOK Related: FR-006-007, N/A — the field metadata it serves, plus: no requirement
 *   defined in this repo for project CRUD; that set (FR-PM-001) lives in
 *   ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`.
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

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD. The requirement set that owns it (FR-PM-001) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document in either repo.
     */
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

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — FK select options
     */
    public function customerOptions(): array
    {
        return $this->ref->customerOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — FK select options
     */
    public function userOptions(): array
    {
        return $this->ref->userOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — FK select options
     */
    public function projectTypeOptions(): array
    {
        return $this->ref->projectTypeOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — status lifecycle options
     */
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

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — priority options
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
     * Field metadata for the Projects tab (FR-006-007 schema).
     *
     * @return array<string, mixed>
     * @since 1.0.0
      *
 * @BABOK Related: FR-006-007 — field-metadata structure — the field metadata the tab surface renders
 */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'      => 'project',
            'table'       => '0_ksf_projectmanagement_projects',
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

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD. The requirement set that owns it (FR-PM-001) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document in either repo.
     */
    public function listRowPage(int $page, int $perPage, array $filters = []): array
    {
        return $this->repo->findPage($page, $perPage, $filters);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD. The requirement set that owns it (FR-PM-001) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document in either repo.
     */
    public function countRows(array $filters = []): int
    {
        return $this->repo->countAll($filters);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD. The requirement set that owns it (FR-PM-001) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document in either repo.
     */
    public function getById(string $id): ?array
    {
        return $this->repo->findById($id);
    }

    // ─── Writes (workflow hooks + activity log) ─────────────────────

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — create, wrapped in the pre/post CRUD workflow hooks
     */
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

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — update, wrapped in the pre/post CRUD workflow hooks
     */
    public function update(string $id, array $data): void
    {
        $payload = ['record_type' => 'project', 'record_id' => $id, 'data' => $data];
        $this->fireWorkflowHook('project', 'before_save', $payload);

        $this->repo->update($id, (array) ($payload['data'] ?? $data));

        $this->fireWorkflowHook('project', 'edited', $payload);
        $this->fireWorkflowHook('project', 'after_save', $payload);
        $this->log('project', $id, 'updated', (string) ($data['name'] ?? $id));
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; project CRUD is specified logic-repo-side as FR-PM-001 (see the class docblock) — delete, wrapped in the pre/post CRUD workflow hooks
     */
    public function delete(string $id): void
    {
        $payload = ['record_type' => 'project', 'record_id' => $id];
        $this->fireWorkflowHook('project', 'before_delete', $payload);
        $this->repo->delete($id);
        $this->fireWorkflowHook('project', 'after_delete', $payload);
        $this->log('project', $id, 'deleted');
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