<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * Reference data lookups shared by the PM tabs (FA-wide DDL sources).
 *
 * Coded against DbConnectionInterface + the shared QueryBuilder, like every
 * other DAO in the module: FaDbAdapter at runtime → native db_*, anything
 * implementing the contract standalone. The three DDL sources below were
 * hand-written SQL strings that went straight to `fetchAll()`, bypassing the
 * query builder even though they were already on the contract — they are now
 * QueryBuilder reads like ProjectTypeRepository's.
 *
 * `debtors_master` and `users` are FrontAccounting core tables, so they are
 * named literally rather than through Schema: the data dictionary owns the
 * `fa_pm_*` tables this module defines, not FA's own schema.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: BR-012 (Project Management)
 */
class ReferenceRepository
{
    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * Customer DDL options (debtors_master → name).
     *
     * @return array<string, string>
     */
    public function customerOptions(): array
    {
        $qb = new QueryBuilder();
        $qb->select(['debtor_no', 'name'])
            ->from('debtors_master')
            ->where('inactive = 0')
            ->orderBy('name');
        $rows = $this->db->fetchAll($qb->toSql(), $qb->getParams());
        $out = [];
        foreach ($rows as $row) {
            $out[$row['debtor_no']] = $row['name'];
        }
        return $out;
    }

    /**
     * User DDL options (users → real_name).
     *
     * @return array<string, string>
     */
    public function userOptions(): array
    {
        $qb = new QueryBuilder();
        $qb->select(['user_id', 'real_name'])
            ->from('users')
            ->where('inactive = 0')
            ->orderBy('real_name');
        $rows = $this->db->fetchAll($qb->toSql(), $qb->getParams());
        $out = [];
        foreach ($rows as $row) {
            $out[$row['user_id']] = $row['real_name'] !== '' ? $row['real_name'] : $row['user_id'];
        }
        return $out;
    }

    /**
     * Project DDL options for task/assignment forms.
     *
     * @return array<string, string>
     */
    public function projectOptions(): array
    {
        $qb = new QueryBuilder();
        $qb->select(['project_id', 'name'])
            ->from(Schema::T_PROJECTS)
            ->where("status <> 'Completed'")
            ->orderBy('start_date DESC')
            ->orderBy('project_id');
        $rows = $this->db->fetchAll($qb->toSql(), $qb->getParams());
        $out = [];
        foreach ($rows as $row) {
            $out[$row['project_id']] = $row['project_id'] . ' - ' . $row['name'];
        }
        return $out;
    }

    /**
     * Project-type DDL options from the ported ProjectTypeRepository.
     *
     * @return array<string, string>
     */
    public function projectTypeOptions(): array
    {
        $rows = (new ProjectTypeRepository($this->db))->findActive();
        $out = [];
        foreach ($rows as $row) {
            $id = (string) $row['id'];
            $out[$id] = $row['name'];
        }
        return $out;
    }
}