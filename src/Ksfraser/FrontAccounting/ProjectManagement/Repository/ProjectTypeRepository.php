<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectType;

/**
 * ProjectTypeRepository — ported onto the shared DbConnectionInterface.
 *
 * The DAO codes against DbConnectionInterface + Schema::projectTypes() (the
 * TableDefinition data dictionary) instead of hand-rolled db_* wrappers. The
 * transport adapter is DI'd: FaDbAdapter at FA runtime (native db_* only),
 * anything implementing the contract standalone. The public API is unchanged,
 * so ProjectTypeService keeps working without modification.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (DAO porting demonstration module)
 * @BABOK Related: FR-PM-006 (Project Types), BR-006 (DDL caching)
 */
class ProjectTypeRepository
{
    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    public function findActive(): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_PROJECT_TYPES)
            ->where('inactive = 0')
            ->orderBy('sort_order')
            ->orderBy('name');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    public function findAll(): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_PROJECT_TYPES)
            ->orderBy('sort_order')
            ->orderBy('name');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    public function findById(int $id): ?ProjectType
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_PROJECT_TYPES)
            ->where('id = :id', ['id' => $id]);
        $row = $this->db->fetchAssoc($qb->toSql(), $qb->getParams());
        return $row !== null ? new ProjectType($row) : null;
    }

    public function save(array $data): int
    {
        $row = [
            'name'        => $data['name'],
            'description' => isset($data['description']) ? $data['description'] : '',
            'inactive'    => isset($data['inactive']) ? (int) $data['inactive'] : 0,
            'sort_order'  => isset($data['sort_order']) ? (int) $data['sort_order'] : 0,
        ];
        $this->db->executeUpdate(Schema::projectTypes()->insertSql(), $row);
        return $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $def = Schema::projectTypes();
        $allowed = array_values(array_diff($def->writableColumns(), ['id']));
        $data['id'] = $id;
        $cols = array_values(array_intersect($allowed, array_keys($data)));
        if (empty($cols)) {
            return;
        }
        $this->db->executeUpdate($def->updateSql($cols), $data);
    }

    public function delete(int $id): void
    {
        $this->db->executeUpdate(Schema::projectTypes()->deleteSql(), ['id' => $id]);
    }
}