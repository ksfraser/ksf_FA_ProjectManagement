<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * Reference data lookups shared by the PM tabs (FA-wide DDL sources).
 *
 * Ported-for-PM DAO: queries run through DbConnectionInterface (FaDbAdapter at
 * runtime → native db_*), so the SQL is written once, transport-agnostic.
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
        $sql = "SELECT debtor_no, name FROM debtors_master"
            . " WHERE !inactive ORDER BY name";
        $rows = $this->db->fetchAll($sql);
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
        $sql = "SELECT user_id, real_name FROM users"
            . " WHERE !inactive ORDER BY real_name";
        $rows = $this->db->fetchAll($sql);
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
        $sql = "SELECT project_id, name FROM " . Schema::T_PROJECTS
            . " WHERE status <> 'Completed' ORDER BY start_date DESC, project_id";
        $rows = $this->db->fetchAll($sql);
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