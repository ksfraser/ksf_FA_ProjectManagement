<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * Project DAO built on the shared data-dictionary + query-builder package.
 *
 * Coded against DbConnectionInterface and Schema::projects() (the TableDefinition
 * data dictionary). The transport adapter is DI'd: FaDbAdapter at FA runtime
 * (native db_* only), PdoDbAdapter or a stub standalone.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (DAO porting demonstration module)
 * @BABOK Related: BR-012 (Project Management)
 */
class ProjectRepository
{
    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findPage(int $page, int $perPage, array $filters = []): array
    {
        $qb = new QueryBuilder();
        $qb->select([
                'project_id', 'name', 'description', 'start_date', 'end_date',
                'budget', 'customer_id', 'project_manager', 'priority',
                'status', 'project_type_id',
            ])
            ->from(Schema::T_PROJECTS);
        $this->applyFilters($qb, $filters);
        $qb->orderBy('start_date DESC')->orderBy('project_id');
        if ($page > 0 && $perPage > 0) {
            $qb->limit($perPage, ($page - 1) * $perPage);
        }
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * @return int
     */
    public function countAll(array $filters = []): int
    {
        $qb = new QueryBuilder();
        $qb->select('COUNT(*)')->from(Schema::T_PROJECTS);
        $this->applyFilters($qb, $filters);
        return (int) $this->db->fetchScalar($qb->toSql(), $qb->getParams(), 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $projectId): ?array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_PROJECTS)
            ->where('project_id = :project_id', ['project_id' => $projectId]);
        return $this->db->fetchAssoc($qb->toSql(), $qb->getParams());
    }

    /**
     * Next sequential project id (dotProject-style: PRJ-0001, PRJ-0002, ...).
     *
     * @param string $prefix Id prefix
     * @return string
     */
    public function nextId(string $prefix = 'PRJ'): string
    {
        $sql = "SELECT project_id FROM " . Schema::T_PROJECTS
            . " WHERE project_id LIKE :pat ORDER BY project_id DESC LIMIT 1";
        $last = $this->db->fetchScalar($sql, ['pat' => $prefix . '-%'], '');
        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return $prefix . '-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Insert a project (dictionary-driven INSERT). Returns the new id.
     *
     * @return string
     */
    public function save(array $data): string
    {
        $def = Schema::projects();
        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'project_id'     => $this->nextId(),
            'description'    => '',
            'budget'         => 0,
            'customer_id'    => null,
            'project_manager'=> '',
            'priority'       => 'Medium',
            'status'         => 'Planning',
            'project_type_id'=> null,
            'created_at'     => $now,
            'updated_at'     => $now,
        ], $data);
        $row['customer_id']    = $this->emptyToNull($row['customer_id']);
        $row['project_type_id'] = $this->emptyToNull($row['project_type_id']);
        $row['end_date']        = $this->emptyToNull($row['end_date'] ?? null);

        $this->db->executeUpdate($def->insertSql(), $row);
        return $row['project_id'];
    }

    /**
     * @return void
     */
    public function update(string $projectId, array $data): void
    {
        $def = Schema::projects();
        $allowed = array_values(array_diff($def->writableColumns(), ['project_id', 'created_at']));
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['project_id'] = $projectId;
        $data['customer_id']    = $this->emptyToNull($data['customer_id'] ?? null);
        $data['project_type_id'] = $this->emptyToNull($data['project_type_id'] ?? null);
        $data['end_date']        = $this->emptyToNull($data['end_date'] ?? null);

        $cols = array_values(array_unique(array_merge(
            array_values(array_intersect($allowed, array_keys($data))),
            ['updated_at']
        )));

        $this->db->executeUpdate($def->updateSql($cols), $data);
    }

    /**
     * @return void
     */
    public function delete(string $projectId): void
    {
        $this->db->executeUpdate(
            Schema::projects()->deleteSql(),
            ['project_id' => $projectId]
        );
    }

    /**
     * Normalize a '' value to null (FA form inputs submit '' for empty dates).
     *
     * @param mixed $value
     * @return mixed
     */
    private function emptyToNull($value)
    {
        return $value === '' ? null : $value;
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['status'])) {
            $qb->where('status = :status', ['status' => $filters['status']]);
        }
        if (!empty($filters['customer_id'])) {
            $qb->where('customer_id = :customer_id', ['customer_id' => (int) $filters['customer_id']]);
        }
        if (!empty($filters['search'])) {
            $qb->where('name LIKE :search', ['search' => '%' . $filters['search'] . '%']);
        }
    }
}