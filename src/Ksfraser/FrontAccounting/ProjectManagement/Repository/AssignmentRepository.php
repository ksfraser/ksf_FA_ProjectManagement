<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * Team-assignment DAO (projects × people) on the shared data-dictionary +
 * query-builder package.
 *
 * The assignments table has a composite primary key (project_id, employee_id).
 * The tab controller treats the pair as a single "assignment_key" rendered as
 * "project_id~employee_id" so the shared CRUD flow (Edit/Delete row actions,
 * pre-loaded update form) works unchanged.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (DAO porting demonstration module)
 * @BABOK Related: BR-012 (Project Management)
 */
class AssignmentRepository
{
    /** Composite-key separator used by the tab layer. */
    const KEY_SEP = '~';

    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * Paged rows; each row gains an "assignment_key" column (project~employee).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findPage(int $page, int $perPage, array $filters = []): array
    {
        $qb = new QueryBuilder();
        $qb->select([
                'project_id', 'employee_id', 'role', 'start_date',
                'end_date', 'allocation_percentage',
            ])
            ->from(Schema::T_ASSIGNMENTS);
        $this->applyFilters($qb, $filters);
        $qb->orderBy('project_id')->orderBy('employee_id');
        if ($page > 0 && $perPage > 0) {
            $qb->limit($perPage, ($page - 1) * $perPage);
        }
        $rows = $this->db->fetchAll($qb->toSql(), $qb->getParams());
        foreach ($rows as $k => $row) {
            $rows[$k]['assignment_key'] = $row['project_id'] . self::KEY_SEP . $row['employee_id'];
        }
        return $rows;
    }

    /**
     * @return int
     */
    public function countAll(array $filters = []): int
    {
        $qb = new QueryBuilder();
        $qb->select('COUNT(*)')->from(Schema::T_ASSIGNMENTS);
        $this->applyFilters($qb, $filters);
        return (int) $this->db->fetchScalar($qb->toSql(), $qb->getParams(), 0);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(string $projectId, string $employeeId): ?array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_ASSIGNMENTS)
            ->where('project_id = :project_id', ['project_id' => $projectId])
            ->where('employee_id = :employee_id', ['employee_id' => $employeeId]);
        return $this->db->fetchAssoc($qb->toSql(), $qb->getParams());
    }

    /**
     * Insert an assignment. Returns the composite key.
     *
     * @return string
     */
    public function save(array $data): string
    {
        $def = Schema::assignments();
        $row = array_merge([
            'role'                 => 'Team Member',
            'end_date'             => null,
            'allocation_percentage'=> 100,
            'created_at'           => date('Y-m-d H:i:s'),
        ], $data);
        $row['end_date'] = $row['end_date'] === '' ? null : $row['end_date'];

        $this->db->executeUpdate($def->insertSql(), $row);
        return $row['project_id'] . self::KEY_SEP . $row['employee_id'];
    }

    /**
     * Update an assignment addressed by its composite key.
     *
     * @param string $compositeKey "project_id~employee_id"
     * @return void
     */
    public function update(string $compositeKey, array $data): void
    {
        list($projectId, $employeeId) = $this->splitKey($compositeKey);

        $def = Schema::assignments();
        $allowed = array_values(array_diff($def->writableColumns(), ['project_id', 'employee_id', 'created_at']));
        $data['project_id'] = $projectId;
        $data['employee_id'] = $employeeId;
        if (isset($data['end_date']) && $data['end_date'] === '') {
            $data['end_date'] = null;
        }

        $cols = array_values(array_intersect($allowed, array_keys($data)));
        $sql = $def->updateSql($cols) . ' AND `employee_id` = :employee_id';
        $this->db->executeUpdate($sql, $data);
    }

    /**
     * Delete an assignment addressed by its composite key.
     *
     * @param string $compositeKey
     * @return void
     */
    public function delete(string $compositeKey): void
    {
        list($projectId, $employeeId) = $this->splitKey($compositeKey);
        $qb = new QueryBuilder();
        $qb->select('*')->from(Schema::T_ASSIGNMENTS)
            ->where('project_id = :project_id', ['project_id' => $projectId])
            ->where('employee_id = :employee_id', ['employee_id' => $employeeId]);
        $sql = (string) preg_replace('/^SELECT \* FROM /', 'DELETE FROM ', $qb->toSql());
        $this->db->executeUpdate($sql, $qb->getParams());
    }

    /**
     * @param string $compositeKey
     * @return string[] [project_id, employee_id]
     */
    public function splitKey(string $compositeKey): array
    {
        return explode(self::KEY_SEP, $compositeKey, 2);
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['project_id'])) {
            $qb->where('project_id = :project_id', ['project_id' => $filters['project_id']]);
        }
        if (!empty($filters['employee_id'])) {
            $qb->where('employee_id = :employee_id', ['employee_id' => $filters['employee_id']]);
        }
    }
}