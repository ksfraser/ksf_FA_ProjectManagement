<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FrontAccounting\ProjectManagement\Repository\AssignmentRepository;
use ksfraser\FrontAccounting\ProjectManagement\Service\AssignmentService;

/**
 * TeamTabController — controller SRP for the PM Team (assignments) tab.
 *
 * The underlying model uses a composite primary key (project_id + employee_id);
 * the controller flattens it into a single `assignment_key` string so the
 * shared AbstractTabController flow (Edit/Delete row actions keyed on one pk)
 * works unchanged. AssignmentRepository::splitKey() round-trips the key.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP) + composite-pk flattening
 * @BABOK Related: BR-012 (Team), FR-006-007
 */
class TeamTabController extends AbstractTabController
{
    /** @var AssignmentService */
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
        $this->service = new AssignmentService();
        $this->filters = [
            'project_id' => isset($_GET['project_id']) ? (string) $_GET['project_id'] : null,
        ];
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'assignment_key';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return AssignmentService::getFieldMetadata();
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        $rows = $this->service->listRowPage($page, $perPage, $this->filters);
        foreach ($rows as $i => $row) {
            $row['assignment_key'] = (string) ($row['project_id'] ?? '') . AssignmentRepository::KEY_SEP . (string) ($row['employee_id'] ?? '');
            $rows[$i] = $row;
        }
        return $rows;
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
        return [
            'project_id' => $this->service->projectOptions(),
            'employee_id' => $this->service->userOptions(),
            'role'        => AssignmentService::roleOptions(),
        ];
    }

    /** {@inheritDoc} */
    protected function preserveParams(): array
    {
        return ['view', 'project_id'];
    }
}