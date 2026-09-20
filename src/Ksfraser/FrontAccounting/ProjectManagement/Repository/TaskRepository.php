<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;

/**
 * Task DAO built on the shared data-dictionary + query-builder package.
 *
 * Coded against DbConnectionInterface and Schema::tasks(). Like the rest of the
 * PM module, the transport adapter is DI'd — FaDbAdapter at FA runtime.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (DAO porting demonstration module)
 * @BABOK Related: BR-012 (Project Management)
 */
class TaskRepository
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
                'task_id', 'project_id', 'parent_task_id', 'name', 'description',
                'assigned_to', 'start_date', 'end_date', 'estimated_hours',
                'actual_hours', 'progress', 'priority', 'status',
            ])
            ->from(Schema::T_TASKS);
        $this->applyFilters($qb, $filters);
        $qb->orderBy('project_id')->orderBy('start_date')->orderBy('task_id');
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
        $qb->select('COUNT(*)')->from(Schema::T_TASKS);
        $this->applyFilters($qb, $filters);
        return (int) $this->db->fetchScalar($qb->toSql(), $qb->getParams(), 0);
    }

    /**
     * Every task row for a project (unbounded — CPM / gantt / csv feeds).
     *
     * @param string $projectId
     * @return array<int,array<string,mixed>>
     */
    public function findAllByProject(string $projectId): array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_TASKS)
            ->where('project_id = :project_id', ['project_id' => $projectId])
            ->orderBy('start_date')->orderBy('task_id');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * Persist a single task's computed scheduling field set.
     *
     * Columns persisted: es/ef/ls/lf/slack/is_critical. This is invoked by
     * SchedulingService->schedule() after the CpmEngine forward/backward
     * pass so the dictionary + the schedule tab read identical values.
     *
     * @param string $taskId
     * @param array{es?:?string,ef?:?string,ls?:?string,lf?:?string,slack?:float,is_critical?:int} $sched
     * @return void
     */
    public function updateSchedule(string $taskId, array $sched): void
    {
        $allow = ['es', 'ef', 'ls', 'lf', 'slack', 'is_critical'];
        $data  = ['task_id' => $taskId];
        foreach ($allow as $k) {
            if (array_key_exists($k, $sched)) {
                $data[$k] = $sched[$k];
            }
        }
        if (count($data) === 1) {
            returninf; // nothing to write
        }
        $this->db->executeUpdate(Schema::tasks()->updateSql(), $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(string $taskId): ?array
    {
        $qb = new QueryBuilder();
        $qb->select('*')
            ->from(Schema::T_TASKS)
            ->where('task_id = :task_id', ['task_id' => $taskId]);
        return $this->db->fetchAssoc($qb->toSql(), $qb->getParams());
    }

    /**
     * Parent-task DDL options for a project (excludes the task itself).
     *
     * @return array<string, string>
     */
    public function parentOptions(string $projectId, string $excludeTaskId = ''): array
    {
        $qb = new QueryBuilder();
        $qb->select(['task_id', 'name'])
            ->from(Schema::T_TASKS)
            ->where('project_id = :project_id', ['project_id' => $projectId]);
        if ($excludeTaskId !== '') {
            $qb->where('task_id <> :self', ['self' => $excludeTaskId]);
        }
        $qb->orderBy('task_id');
        $rows = $this->db->fetchAll($qb->toSql(), $qb->getParams());
        $out = [];
        foreach ($rows as $row) {
            $out[$row['task_id']] = $row['task_id'] . ' - ' . $row['name'];
        }
        return $out;
    }

    /**
     * Next sequential task id (TSK-0001, TSK-0002, ...).
     *
     * @return string
     */
    public function nextId(): string
    {
        $sql = "SELECT task_id FROM " . Schema::T_TASKS
            . " WHERE task_id LIKE :pat ORDER BY task_id DESC LIMIT 1";
        $last = $this->db->fetchScalar($sql, ['pat' => 'TSK-%'], '');
        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return 'TSK-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Insert a task (dictionary-driven INSERT). Returns the new task id.
     *
     * @return string
     */
    public function save(array $data): string
    {
        $def = Schema::tasks();
        $now = date('Y-m-d H:i:s');
        $row = array_merge([
            'task_id'        => $this->nextId(),
            'parent_task_id' => '',
            'description'    => '',
            'assigned_to'    => null,
            'start_date'     => null,
            'end_date'       => null,
            'estimated_hours'=> 0,
            'actual_hours'   => 0,
            'progress'       => 0,
            'priority'       => 'Medium',
            'status'         => 'Not Started',
            'created_at'     => $now,
            'updated_at'     => $now,
        ], $data);
        $row['assigned_to'] = $this->emptyToNull($row['assigned_to']);
        $row['start_date']  = $this->emptyToNull($row['start_date']);
        $row['end_date']    = $this->emptyToNull($row['end_date']);

        $this->db->executeUpdate($def->insertSql(), $row);
        return $row['task_id'];
    }

    /**
     * @return void
     */
    public function update(string $taskId, array $data): void
    {
        $def = Schema::tasks();
        $allowed = array_values(array_diff($def->writableColumns(), ['task_id', 'created_at']));
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['task_id']    = $taskId;
        $data['assigned_to'] = $this->emptyToNull($data['assigned_to'] ?? null);
        $data['start_date']  = $this->emptyToNull($data['start_date'] ?? null);
        $data['end_date']    = $this->emptyToNull($data['end_date'] ?? null);

        $cols = array_values(array_unique(array_merge(
            array_values(array_intersect($allowed, array_keys($data))),
            ['updated_at']
        )));

        $this->db->executeUpdate($def->updateSql($cols), $data);
    }

    /**
     * Delete a task and its OpenProject-style progress row (cascade by hand —
     * progress holds a direct unique FK).
     *
     * @return void
     */
    public function delete(string $taskId): void
    {
        $this->db->executeUpdate(
            'DELETE FROM ' . Schema::T_PROGRESS . ' WHERE task_id = :task_id',
            ['task_id' => $taskId]
        );
        Schema::tasks();
        $this->db->executeUpdate(
            Schema::tasks()->deleteSql(),
            ['task_id' => $taskId]
        );
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function emptyToNull($value)
    {
        return $value === '' ? null : $value;
    }

    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (!empty($filters['project_id'])) {
            $qb->where('project_id = :project_id', ['project_id' => $filters['project_id']]);
        }
        if (!empty($filters['status'])) {
            $qb->where('status = :status', ['status' => $filters['status']]);
        }
        if (!empty($filters['assigned_to'])) {
            $qb->where('assigned_to = :assigned_to', ['assigned_to' => $filters['assigned_to']]);
        }
        if (!empty($filters['search'])) {
            $qb->where('name LIKE :search', ['search' => '%' . $filters['search'] . '%']);
        }
    }
}