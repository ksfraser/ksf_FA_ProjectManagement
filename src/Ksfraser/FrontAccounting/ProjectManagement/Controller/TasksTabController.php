<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FrontAccounting\ProjectManagement\Service\TaskService;

/**
 * TasksTabController — controller SRP for the PM Tasks tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-PM-010-001, FR-006-007 — the tab surface and its field metadata.
 *   Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
class TasksTabController extends AbstractTabController
{
    /** @var TaskService */
    private $service;

    /** @var array<string, mixed> Active GET filters */
    private $filters;

    /**
     * @param \Ksfraser\Frontaccounting\HTML\TabContext|null $context DI request state
     * @param array<string, mixed>                            $options
     *
     * @since 1.0.0
      *
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new TaskService();
        $this->filters = [
            'project_id' => isset($_GET['project_id']) ? (string) $_GET['project_id'] : null,
            'status'     => isset($_GET['status']) ? (string) $_GET['status'] : null,
        ];
    }

    /**
 * @BABOK Related: FR-006-007 — field-metadata structure — the primary-key field name the tab surface needs
 */
    protected function getPkField(): string
    {
        return 'task_id';
    }

    /**
 * @BABOK Related: FR-006-007 — field-metadata structure — field metadata drives the tab surface form
 */
    protected function getFieldMetadata(): array
    {
        return TaskService::getFieldMetadata();
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function listRows(int $page, int $perPage): array
    {
        return $this->service->listRowPage($page, $perPage, $this->filters);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function countRows(): int
    {
        return $this->service->countRows($this->filters);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById($pk);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function createRecord(array $data)
    {
        return $this->service->create($data);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->update($pk, $data);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for task management. The requirement set that owns it (FR-PM-002) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function deleteRecord(string $pk): void
    {
        $this->service->delete($pk);
    }

    /**
 * @BABOK Related: FR-006-007 — field-metadata structure — foreign-key select options for the form
 */
    protected function fkOptions(): array
    {
        // Parent task options are scoped to the task's project (selected in the
        // entry form, or read from the record being edited).
        $projectId = isset($_POST['project_id']) ? (string) $_POST['project_id'] : (isset($_GET['project_id']) ? (string) $_GET['project_id'] : '');
        $exclude = '';
        $recordId = $this->context->getRecordId();
        if ($projectId === '' && $recordId !== '') {
            $record = $this->findRecord($recordId);
            if ($record !== null) {
                $projectId = (string) ($record['project_id'] ?? '');
                $exclude = $recordId;
            }
        }

        return [
            'project_id'     => $this->service->projectOptions(),
            'parent_task_id' => $this->service->parentOptions($projectId, $exclude),
            'assigned_to'    => $this->service->userOptions(),
            'priority'       => TaskService::priorityOptions(),
            'status'         => TaskService::statusOptions(),
        ];
    }

    /**
 * @BABOK Related: FR-PM-010-001, BR-PM-010 — the PM app-shell / front-controller tab surface — keeps the selected tab across pager links
 */
    protected function preserveParams(): array
    {
        return ['view', 'project_id', 'status'];
    }
}