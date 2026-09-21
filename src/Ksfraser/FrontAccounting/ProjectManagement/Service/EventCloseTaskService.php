<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * EventCloseTaskService — FR-PM-007-002 task time-window close subscriber.
 *
 * Consumes `ksf_event_closed` for ksf_FA_ProjectManagement: when a closed
 * event's DTO carries a task_id that matches a row in `0_fa_pm_tasks`, the
 * task's logged-time window is SEALED and the recorded worked window
 * (`closed_at - started_at`) is snapshotted into the task's
 * `0_fa_pm_task_progress` row (the task uniquely owns one) as `work_hours`
 * with `status = 'Closed'`.
 *
 * The module writes ONLY its own tables — it never creates or touches
 * timesheet/expense rows (FR-PM-007-002 req 3). Idempotent: a repeat
 * broadcast for the same task is a no-op — nothing is re-written and the
 * window is never re-opened (req 2). Unknown task / missing task_id is a
 * silent no-op (AZZ). The hooks wrapper contains any failure so this
 * subscriber never breaks other listeners of the same broadcast (req 4).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: BR-007, FR-PM-007-002, FR-CAL-007-002
 */
class EventCloseTaskService
{
    /** Time-window-closed marker persisted on the task's progress row. */
    const CLOSED_STATUS = 'Closed';

    /** @var DbConnectionInterface */
    private $db;

    public function __construct(?DbConnectionInterface $db = null)
    {
        $this->db = $db ?? Schema::adapter();
    }

    /**
     * Close the task's time window on a ksf_event_closed broadcast.
     *
     * @param object|array $dto EventClosedDto, or its array form
     * @return array{closed: bool, task_id: string|null, recorded_hours: float,
     *               already_closed?: bool, reason?: string}
     */
    public function onEventClosed($dto): array
    {
        $payload = $this->normalizeDto($dto);
        $taskId  = $this->value($payload, 'task_id');

        if ($taskId === '' || $taskId === null) {
            return array('closed' => false, 'task_id' => null, 'recorded_hours' => 0.0, 'reason' => 'no_task_id');
        }

        $taskId = (string) $taskId;

        $task = $this->findTask($taskId);
        if ($task === null) {
            return array('closed' => false, 'task_id' => $taskId, 'recorded_hours' => 0.0, 'reason' => 'unknown_task');
        }

        $hours = $this->eventHours($payload);
        $progress = $this->findProgress($taskId);

        if ($progress !== null && $this->isClosed($progress)) {
            return array('closed' => true, 'task_id' => $taskId, 'recorded_hours' => $hours, 'already_closed' => true);
        }

        $this->writeClosedWindow($taskId, $hours, $progress !== null);

        return array('closed' => true, 'task_id' => $taskId, 'recorded_hours' => $hours, 'already_closed' => false);
    }

    // -----------------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------------

    /**
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
     * @param string $taskId
     * @return array<string, mixed>|null
     */
    private function findProgress(string $taskId): ?array
    {
        $sql = "SELECT progress_id, task_id, status FROM " . Schema::T_PROGRESS
            . " WHERE task_id = :task_id LIMIT 1";
        return $this->db->fetchAssoc($sql, array('task_id' => $taskId));
    }

    /**
     * @param array<string, mixed> $progress progress row
     * @return bool true when the row already carries the closed marker
     */
    private function isClosed(array $progress): bool
    {
        $status = isset($progress['status']) ? trim((string) $progress['status']) : '';
        return strtolower($status) === strtolower(self::CLOSED_STATUS);
    }

    /**
     * Persist the sealed worked window on the task's own progress row.
     *
     * Inserts when no progress row exists; otherwise marks the existing row
     * closed with the freshly recorded window. Only EVERY WRITE touches the
     * module's own table (FR-PM-007-002 req 3).
     *
     * @param string  $taskId
     * @param float   $hours  recorded worked window (closed_at - started_at)
     * @param bool    $exists whether a progress row already exists
     * @return void
     */
    private function writeClosedWindow(string $taskId, float $hours, bool $exists): void
    {
        $now = date('Y-m-d H:i:s');

        if ($exists) {
            $sql = "UPDATE " . Schema::T_PROGRESS
                . " SET work_hours = :work_hours, status = :status, updated_at = :updated_at"
                . " WHERE task_id = :task_id";
            $this->db->executeUpdate($sql, array(
                'work_hours' => $hours,
                'status'     => self::CLOSED_STATUS,
                'updated_at' => $now,
                'task_id'    => $taskId,
            ));
            return;
        }

        $sql = "INSERT INTO " . Schema::T_PROGRESS
            . " (task_id, progress_mode, work_hours, remaining_hours, percent_complete, status, baseline_hours, updated_at)"
            . " VALUES (:task_id, 'work_based', :work_hours, 0, 0, :status, 0, :updated_at)";
        $this->db->executeUpdate($sql, array(
            'task_id'    => $taskId,
            'work_hours' => $hours,
            'status'     => self::CLOSED_STATUS,
            'updated_at' => $now,
        ));
    }

    /**
     * @param array<string, mixed> $payload normalized DTO
     * @return float recorded worked window in hours (closed_at - started_at)
     */
    private function eventHours(array $payload): float
    {
        $from = strtotime((string) ($payload['started_at'] ?? ''));
        $to   = strtotime((string) ($payload['closed_at'] ?? ''));
        if ($from === false || $to === false || $to <= $from) {
            return 0.0;
        }
        return round(($to - $from) / 3600, 2);
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