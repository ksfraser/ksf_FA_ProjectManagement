<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * Activity-log DAO on the shared data-dictionary + query-builder package.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (DAO porting demonstration module)
 * @BABOK Related: BR-012 (Project Management)
 */
class ActivityLogRepository
{
    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * Write an activity-log row.
     *
     * @param string      $entityType 'project'|'task'|'assignment'|...
     * @param string      $entityId
     * @param string      $action
     * @param string      $details
     * @param string|null $actor     Current user id (null → resolve at call)
     * @return int New log id
     */
    public function log(string $entityType, string $entityId, string $action, string $details = '', ?string $actor = null): int
    {
        if ($actor === null) {
            $actor = 'system';
            if (isset($_SESSION['wa_current_user']) && is_object($_SESSION['wa_current_user'])) {
                $user = $_SESSION['wa_current_user']->user ?? '';
                if ($user !== '') {
                    $actor = $user;
                }
            }
        }

        $now = date('Y-m-d H:i:s');
        $row = [
            'activity_type' => 'crud',
            'entity_type'   => $entityType,
            'entity_id'     => $entityId,
            'user_id'       => $actor,
            'action'        => $action,
            'details'       => $details,
            'old_values'    => null,
            'new_values'    => null,
            'ip_address'    => isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : null,
            'created_at'    => $now,
        ];

        $this->db->executeUpdate(Schema::activityLog()->insertSql(), $row);
        return $this->db->lastInsertId();
    }

    /**
     * Most recent activity rows (dashboard feed).
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function recent(int $limit = 10): array
    {
        $qb = new QueryBuilder();
        $qb->select(['id', 'entity_type', 'entity_id', 'user_id', 'action', 'details', 'created_at'])
            ->from(Schema::T_ACTIVITY)
            ->orderBy('id DESC')
            ->limit($limit);
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }
}