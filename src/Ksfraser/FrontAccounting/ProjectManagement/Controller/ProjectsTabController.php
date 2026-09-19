<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FrontAccounting\ProjectManagement\Service\ProjectService;

/**
 * ProjectsTabController — controller SRP for the PM Projects tab.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: BR-012 (Projects), FR-006-007
 */
class ProjectsTabController extends AbstractTabController
{
    /** @var ProjectService */
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
        $this->service = new ProjectService();
        $this->filters = ['status' => isset($_GET['status']) ? (string) $_GET['status'] : null];
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'project_id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return ProjectService::getFieldMetadata();
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
        return [
            'customer_id'     => $this->service->customerOptions(),
            'project_manager' => $this->service->userOptions(),
            'project_type_id' => $this->service->projectTypeOptions(),
            'priority'        => ProjectService::priorityOptions(),
            'status'          => ProjectService::statusOptions(),
        ];
    }

    /** {@inheritDoc} */
    protected function preserveParams(): array
    {
        return ['view', 'status'];
    }
}