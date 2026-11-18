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
 * @BABOK Related: FR-PM-010-001, FR-006-007 — the tab surface and its field metadata.
 *   Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
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
      *
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    public function __construct($context = null, array $options = [])
    {
        parent::__construct($context, $options);
        $this->service = new AssignmentService();
        $this->filters = [
            'project_id' => isset($_GET['project_id']) ? (string) $_GET['project_id'] : null,
        ];
    }

    /**
 * @BABOK Related: FR-006-007 — field-metadata structure — the composite-key column the tab surface needs
 */
    protected function getPkField(): string
    {
        return 'assignment_key';
    }

    /**
 * @BABOK Related: FR-006-007 — field-metadata structure — field metadata drives the tab surface form
 */
    protected function getFieldMetadata(): array
    {
        return AssignmentService::getFieldMetadata();
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function listRows(int $page, int $perPage): array
    {
        $rows = $this->service->listRowPage($page, $perPage, $this->filters);
        foreach ($rows as $i => $row) {
            $row['assignment_key'] = (string) ($row['project_id'] ?? '') . AssignmentRepository::KEY_SEP . (string) ($row['employee_id'] ?? '');
            $rows[$i] = $row;
        }
        return $rows;
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function countRows(): int
    {
        return $this->service->countRows($this->filters);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function findRecord(string $pk): ?array
    {
        return $this->service->getById($pk);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function createRecord(array $data)
    {
        return $this->service->create($data);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 */
    protected function updateRecord(string $pk, array $data): void
    {
        $this->service->update($pk, $data);
    }

    /**
 * @BABOK Related: N/A — no requirement defined in this repo for resource allocation. The requirement set that owns it (FR-PM-003) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
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
        return [
            'project_id' => $this->service->projectOptions(),
            'employee_id' => $this->service->userOptions(),
            'role'        => AssignmentService::roleOptions(),
        ];
    }

    /**
 * @BABOK Related: FR-PM-010-001, BR-PM-010 — the PM app-shell / front-controller tab surface — keeps the selected tab across pager links
 */
    protected function preserveParams(): array
    {
        return ['view', 'project_id'];
    }
}