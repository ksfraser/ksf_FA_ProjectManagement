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
 * @BABOK Related: BR-012 (Tasks), FR-006-007
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

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'task_id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return TaskService::getFieldMetadata();
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        return $this->service->listRowPage($page, $perPage, $this->filters);
    }

    /** {@inheritDoc} */
    protected function countRows(): int
    {
        return $this->service->countRows($this->filters);
    }

    /** {@inheritDoc} */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById($pk);
    }

    /** {@inheritDoc} */
    protected function createRecord(array $data)
    {
        return $this->service->create($data);
    }

    /** {@inheritDoc} */
    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->update($pk, $data);
    }

    /** {@inheritDoc} */
    protected function deleteRecord(string $pk): void
    {
        $this->service->delete($pk);
    }

    /** {@inheritDoc} */
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

    /** {@inheritDoc} */
    protected function preserveParams(): array
    {
        return ['view', 'project_id', 'status'];
    }
}