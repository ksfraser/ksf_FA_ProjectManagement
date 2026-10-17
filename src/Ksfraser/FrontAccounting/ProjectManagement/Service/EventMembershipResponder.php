<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * EventMembershipResponder — FR-PM-007-001 read-only membership responder.
 *
 * Answers the `ksf_event_classify_attendees` broadcast for
 * ksf_FA_ProjectManagement: when a closed event's DTO carries a project_id
 * (or a task_id that resolves to a `0_fa_pm_tasks` row), every attendee email
 * that bridges through FA's users table to a user assigned on
 * `0_fa_pm_assignments` for that project is a PROJECT TEAM MEMBER.
 *
 * Identity chain (FR-PM-007-001): email → users.email → users.user_id →
 * 0_fa_pm_assignments.employee_id. The Team tab stores FA user_id values in
 * employee_id (see TeamTabController → userOptions()), so the user handle is
 * the assignment key this module owns.
 *
 * Guarantees (FR-PM-007-001 req 3/5): the responder is READ-ONLY — it uses
 * the module's own DAO layer via DbConnectionInterface (FaDbAdapter → native
 * db_* at runtime) and never writes during classification. Attendees that
 * cannot be resolved (email missing from users, or user not assigned) are
 * simply NOT listed as member — they stay unclassified and the caller's
 * aggregation decides (FR-PM-007-001 req 2). The hooks wrapper contains any
 * DB failure so a responder error never aborts the caller's classification
 * (req 4).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: BR-007, FR-PM-007-001, FR-CAL-007-003
 */
class EventMembershipResponder
{
    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * Member attendee emails for a closed-event DTO.
     *
     * No-op (empty response) when the DTO carries neither a project_id nor a
     * task_id known to this module (FR-PM-007-001 AZZ). Task implies project
     * only when the task row exists (FR-PM-007-001 req 1).
     *
     * @param object|array $dto EventClosedDto, or its array form
     * @return string[] lower-cased, de-duplicated member emails
     */
    public function respondMembership($dto): array
    {
        $payload   = $this->normalizeDto($dto);
        $taskId    = $this->value($payload, 'task_id');
        $projectId = $this->value($payload, 'project_id');

        if (($projectId === '' || $projectId === null)
            && ($taskId === '' || $taskId === null)) {
            return array();
        }

        if ($taskId !== '' && $taskId !== null) {
            $task = $this->findTask((string) $taskId);
            if ($task === null || empty($task['project_id'])) {
                return array();
            }
            $projectId = (string) $task['project_id'];
        }

        if ($projectId === '' || $projectId === null) {
            return array();
        }

        $assigneeKeys = $this->assigneeUserIds((string) $projectId);
        if (empty($assigneeKeys)) {
            return array();
        }

        $members = array();
        foreach ($this->attendeeEmails($payload) as $email) {
            $userId = $this->resolveUserId($email);
            if ($userId === '') {
                continue; // identity bridge miss (BON) -> not classified member
            }
            if (isset($assigneeKeys[strtolower($userId)])) {
                $members[$email] = true;
            }
        }

        return array_keys($members);
    }

    // -----------------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------------

    /**
     * Load a task's project id (task implies project only when it exists).
     *
     * @param string $taskId
     * @return array<string, mixed>|null
     */
    private function findTask(string $taskId): ?array
    {
        $sql = "SELECT task_id, project_id FROM " . Schema::T_TASKS
            . " WHERE task_id = :task_id LIMIT 1";
        return $this->db->fetchAssoc($sql, array('task_id' => $taskId));
    }

    /**
     * Assignee user_id keys (lower-cased) for an event project.
     *
     * @param string $projectId
     * @return array<string, true>
     */
    private function assigneeUserIds(string $projectId): array
    {
        $sql = "SELECT employee_id FROM " . Schema::T_ASSIGNMENTS
            . " WHERE project_id = :project_id";
        $rows = $this->db->fetchAll($sql, array('project_id' => $projectId));

        $keys = array();
        foreach ($rows as $row) {
            $userId = isset($row['employee_id']) ? trim((string) $row['employee_id']) : '';
            if ($userId !== '') {
                $keys[strtolower($userId)] = true;
            }
        }
        return $keys;
    }

    /**
     * Resolve attendee email → users.user_id (case-insensitive, first match).
     *
     * @param string $email
     * @return string user id, or '' when the email is unknown to users
     */
    private function resolveUserId(string $email): string
    {
        $sql = "SELECT user_id FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1";
        $row = $this->db->fetchAssoc($sql, array('email' => $email));
        if ($row === null || !isset($row['user_id'])) {
            return '';
        }
        return trim((string) $row['user_id']);
    }

    /**
     * @param array<string, mixed> $payload normalized DTO
     * @return string[] lower-cased, de-duplicated attendee emails
     */
    private function attendeeEmails(array $payload): array
    {
        $list = isset($payload['attendee_emails']) && is_array($payload['attendee_emails'])
            ? $payload['attendee_emails'] : array();
        $seen = array();
        foreach ($list as $email) {
            if (!is_scalar($email)) {
                continue;
            }
            $email = strtolower(trim((string) $email));
            if ($email === '') {
                continue;
            }
            $seen[$email] = true;
        }
        return array_keys($seen);
    }

    /**
     * @param object|array $dto
     * @return array<string, mixed>
     */
    private function normalizeDto($dto): array
    {
        if (is_array($dto)) {
            return $dto;
        }
        if (is_object($dto) && method_exists($dto, 'toArray')) {
            $arr = $dto->toArray();
            return is_array($arr) ? $arr : array();
        }
        return array();
    }

    /**
     * @param array<string, mixed> $payload
     * @param string               $key
     * @return mixed
     */
    private function value(array $payload, string $key)
    {
        if (array_key_exists($key, $payload)) {
            return $payload[$key];
        }
        return $payload[strtolower($key)] ?? null;
    }
}