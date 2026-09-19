<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Controller;

use ksfraser\FrontAccounting\Common\App\AbstractTabController;
use ksfraser\FrontAccounting\ProjectManagement\Service\ProjectTypeService;

/**
 * ProjectTypesTabController — controller SRP for the PM Project Types tab.
 *
 * Wraps the existing ProjectTypeService (public API unchanged after its
 * repository was ported onto DbConnectionInterface). Cache invalidation stays
 * in the service layer.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §11 (controller SRP)
 * @BABOK Related: FR-PM-006 (Project Types), BR-006 (DDL caching), FR-006-007
 */
class ProjectTypesTabController extends AbstractTabController
{
    /** @var ProjectTypeService */
    private $service;

    /**
     * @param \Ksfraser\Frontaccounting\HTML\TabContext|null $context DI request state
     * @param array<string, mixed>                            $options
     *
     * @since 1.0.0
     */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new ProjectTypeService();
    }

    /** {@inheritDoc} */
    protected function getPkField(): string
    {
        return 'id';
    }

    /** {@inheritDoc} */
    protected function getFieldMetadata(): array
    {
        return [
            'entity'      => 'project_type',
            'table'       => '0_fa_pm_project_types',
            'label'       => 'Project Type',
            'labelPlural' => 'Project Types',
            'hookPrefix'  => 'ProjectType',
            'pk'          => 'id',
            'fields'      => [
                'id' => [
                    'label' => 'ID', 'type' => 'text',
                    'showInForm' => false, 'showInTable' => true,
                ],
                'name' => [
                    'label' => 'Name', 'type' => 'text',
                    'required' => true, 'max' => 50,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'description' => [
                    'label' => 'Description', 'type' => 'textarea',
                    'showInTable' => false, 'showInForm' => true,
                ],
                'sort_order' => [
                    'label' => 'Sort Order', 'type' => 'number', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
                'inactive' => [
                    'label' => 'Inactive', 'type' => 'checkbox', 'default' => 0,
                    'showInTable' => true, 'showInForm' => true,
                ],
            ],
            'fk_ddls'  => [],
            'ddlHooks' => [],
            'tableSettings' => ['orderBy' => 'sort_order, name'],
        ];
    }

    /** {@inheritDoc} */
    protected function listRows(int $page, int $perPage): array
    {
        return array_slice($this->service->listAll(), ($page - 1) * $perPage, $perPage);
    }

    /** {@inheritDoc} */
    protected function countRows(): int
    {
        return count($this->service->listAll());
    }

    /** {@inheritDoc} */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById((int) $pk);
    }

    /** {@inheritDoc} */
    protected function createRecord(array $data)
    {
        return $this->service->create($data);
    }

    /** {@inheritDoc} */
    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->update((int) $pk, $data);
    }

    /** {@inheritDoc} */
    protected function deleteRecord(string $pk): void
    {
        $this->service->delete((int) $pk);
    }
}