<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\Common\Traits\WorkflowHooksTrait;
use ksfraser\FrontAccounting\ProjectManagement\Repository\AssignmentRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ReferenceRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ActivityLogRepository;

/**
 * AssignmentService — business logic + workflow hooks for the Team tab.
 *
 * Fires lifecycle hooks as `pm_assignment_before_save`, `pm_assignment_edited`,
 * `pm_assignment_after_save`, `pm_assignment_before_delete`, etc., on top of
 * the AssignmentRepository DAO (DbConnectionInterface).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (pre/post workflow hooks on the DAO layer)
 * @BABOK Related: FR-006-007, N/A — the field metadata it serves, plus: no requirement
 *   defined in this repo for resource allocation; that set (FR-PM-003) lives in
 *   ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`.
 */
class AssignmentService
{
    use WorkflowHooksTrait;

    /** @var AssignmentRepository */
    private $repo;

    /** @var ReferenceRepository */
    private $ref;

    /** @var ActivityLogRepository|null */
    private $log;

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function __construct(
        ?AssignmentRepository $repo = null,
        ?ReferenceRepository $ref = null,
        ?ActivityLogRepository $log = null
    ) {
        $this->repo = $repo ?? new AssignmentRepository();
        $this->ref  = $ref  ?? new ReferenceRepository();
        $this->log  = $log ?? new ActivityLogRepository();
        $this->registerWorkflowType('assignment', 'pm_assignment');
    }

    // ─── Reference / DDL ────────────────────────────────────────────

    /**
     * @BABOK Related: N/A — no requirement in this repo; resource allocation is specified logic-repo-side as FR-PM-003 (see the class docblock) — FK select options
     */
    public function projectOptions(): array
    {
        return $this->ref->projectOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; resource allocation is specified logic-repo-side as FR-PM-003 (see the class docblock) — FK select options
     */
    public function userOptions(): array
    {
        return $this->ref->userOptions();
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; resource allocation is specified logic-repo-side as FR-PM-003 (see the class docblock) — role select options
     */
    public static function roleOptions(): array
    {
        return [
            'Project Manager' => 'Project Manager',
            'Tech Lead'       => 'Tech Lead',
            'Developer'       => 'Developer',
            'Analyst'         => 'Analyst',
            'Designer'        => 'Designer',
            'Tester'          => 'Tester',
            'Team Member'     => 'Team Member',
        ];
    }

    /**
     * Field metadata for the Team tab (FR-006-007 schema).
     *
     * @return array<string, mixed>
     * @since 1.0.0
      *
 * @BABOK Related: FR-006-007 — field-metadata structure — the field metadata the tab surface renders
 */
    public static function getFieldMetadata(): array
    {
        return [
            'entity'      => 'assignment',
            'table'       => '0_ksf_projectmanagement_assignments',
            'label'       => 'Team Member',
            'labelPlural' => 'Team',
            'hookPrefix'  => 'Assignment',
            'pk'          => 'assignment_key',
            'fields'      => [
                'assignment_key' => [
                    'label' => 'Assignment', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'project_id' => [
                    'label' => 'Project', 'type' => 'select',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'employee_id' => [
                    'label' => 'Team Member', 'type' => 'select',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'role' => [
                    'label' => 'Role', 'type' => 'select', 'default' => 'Team Member',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'start_date' => [
                    'label' => 'Start Date', 'type' => 'date', 'default' => date('Y-m-d'),
                    'showInTable' => true, 'showInForm' => true,
                ],
                'end_date' => [
                    'label' => 'End Date', 'type' => 'date',
                    'showInTable' => true, 'showInForm' => true,
                ],
                'allocation_percentage' => [
                    'label' => 'Allocation %', 'type' => 'number', 'default' => 100,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'project_id, employee_id'],
        ];
    }

    // ─── Reads ──────────────────────────────────────────────────────

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function listRowPage(int $page, int $perPage, array $filters = []): array
    {
        return $this->repo->findPage($page, $perPage, $filters);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function countRows(array $filters = []): int
    {
        return $this->repo->countAll($filters);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
     */
    public function getById(string $compositeKey): ?array
    {
        list($projectId, $employeeId) = $this->repo->splitKey($compositeKey);
        return $this->repo->find($projectId, $employeeId);
    }

    // ─── Writes (workflow hooks + activity log) ─────────────────────

    /**
     * @BABOK Related: N/A — no requirement in this repo; resource allocation is specified logic-repo-side as FR-PM-003 (see the class docblock) — create, wrapped in the pre/post CRUD workflow hooks
     */
    public function create(array $data): string
    {
        $payload = ['record_type' => 'assignment', 'data' => $data];
        $this->fireWorkflowHook('assignment', 'before_save', $payload);

        $key = $this->repo->save((array) ($payload['data'] ?? $data));

        $payload['record_id'] = $key;
        $this->fireWorkflowHook('assignment', 'new', $payload);
        $this->fireWorkflowHook('assignment', 'after_save', $payload);
        $this->log('assignment', $key, 'created');

        return $key;
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; resource allocation is specified logic-repo-side as FR-PM-003 (see the class docblock) — update, wrapped in the pre/post CRUD workflow hooks
     */
    public function update(string $compositeKey, array $data): void
    {
        $payload = ['record_type' => 'assignment', 'record_id' => $compositeKey, 'data' => $data];
        $this->fireWorkflowHook('assignment', 'before_save', $payload);

        $this->repo->update($compositeKey, (array) ($payload['data'] ?? $data));

        $this->fireWorkflowHook('assignment', 'edited', $payload);
        $this->fireWorkflowHook('assignment', 'after_save', $payload);
        $this->log('assignment', $compositeKey, 'updated');
    }

    /**
     * @BABOK Related: N/A — no requirement in this repo; resource allocation is specified logic-repo-side as FR-PM-003 (see the class docblock) — delete, wrapped in the pre/post CRUD workflow hooks
     */
    public function delete(string $compositeKey): void
    {
        $payload = ['record_type' => 'assignment', 'record_id' => $compositeKey];
        $this->fireWorkflowHook('assignment', 'before_delete', $payload);
        $this->repo->delete($compositeKey);
        $this->fireWorkflowHook('assignment', 'after_delete', $payload);
        $this->log('assignment', $compositeKey, 'deleted');
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