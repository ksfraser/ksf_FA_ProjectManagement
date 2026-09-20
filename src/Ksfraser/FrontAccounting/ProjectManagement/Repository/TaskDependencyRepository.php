<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ProjectManagement\Repository;

use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\CommonDb\Contract\DbConnectionInterface;

/**
 * Task Dependency DAO — CPM precedence edges persisted as adjacency rows.
 *
 * Each row is one predecessor→task edge (FS/SS/FF/SF) carrying an optional
 * lag. write path guarded by TaskDependencyService (validates type, rejects
 * self-reference and cycles); the Engine owns graph math and stays pure.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 */
class TaskDependencyRepository
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
                'dependency_id', 'task_id', 'predecessor_id',
                'dependency_type', 'lag', 'created_at',
            ])
            ->from(Schema::T_TASK_DEPENDENCIES)
            ->orderBy('task_id')->orderBy('predecessor_id');
        if ($page > 0 && $perPage > 0) {
            $qb->limit($perPage, ($page - 1) * $perPage);
        }
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllFor(string $taskId): array
    {
        $qb = new QueryBuilder();
        $qb->select(['dependency_id', 'task_id', 'predecessor_id', 'dependency_type', 'lag'])
            ->from(Schema::T_TASK_DEPENDENCIES)
            ->where('task_id = :task_id', ['task_id' => $taskId])
            ->orderBy('predecessor_id');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * All dependency edges belonging to a project, resolved through the task
     * dictionary (successor row's project_id). Feeds CpmEngine + gantt.
     *
     * @param string $projectId
     * @return array<int,array<string,mixed>>
     */
    public function findAllByProject(string $projectId): array
    {
        $qb = new QueryBuilder();
        $qb->select(['d.dependency_id', 'd.task_id', 'd.predecessor_id',
                     'd.dependency_type', 'd.lag'])
            ->from(Schema::T_TASK_DEPENDENCIES . ' AS d')
            ->join('INNER', Schema::T_TASKS . ' AS t', 't.task_id = d.task_id')
            ->where('t.project_id = :project_id', ['project_id' => $projectId])
            ->orderBy('d.predecessor_id');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * Alias for the page-2 consumers that ask per-project by name.
     *
     * @param string $projectId
     * @return array<int,array<string,mixed>>
     */
    public function findAllForProject(string $projectId): array
    {
        return $this->findAllByProject($projectId);
    }

    /**
     * @return string
     */
    public function nextId(): string
    {
        $sql = "SELECT dependency_id FROM " . Schema::T_TASK_DEPENDENCIES
            . " WHERE dependency_id LIKE :pat ORDER BY dependency_id DESC LIMIT 1";
        $last = $this->db->fetchScalar($sql, ['pat' => 'DEP-'], '');
        $seq = 1;
        if (is_string($last) && preg_match('/-(\d+)$/', $last, $m)) {
            $seq = (int) $m[1] + 1;
        }
        return 'DEP-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return string
     */
    public function insert(array $data): string
    {
        $row = [
            'dependency_id'   => $this->nextId(),
            'task_id'         => $data['task_id'],
            'predecessor_id'  => $data['predecessor_id'],
            'dependency_type' => $data['dependency_type'] ?? 'FS',
            'lag'             => $data['lag'] ?? 0.00,
            'created_at'      => date('Y-m-d H:i:s'),
        ];
        $def = Schema::taskDependencies();
        $this->db->executeUpdate($def->insertSql(), $row);
        return $row['dependency_id'];
    }

    /**
     * @return void
     */
    public function delete(string $taskId, string $predecessorId): void
    {
        $this->db->executeUpdate(
            'DELETE FROM ' . Schema::T_TASK_DEPENDENCIES
            . ' WHERE task_id = :task_id AND predecessor_id = :predecessor_id',
            ['task_id' => $taskId, 'predecessor_id' => $predecessorId]
        );
    }

    /**
     * @return void
     */
    public function deleteAllFor(string $taskId): void
    {
        $this->db->executeUpdate(
            'DELETE FROM ' . Schema::T_TASK_DEPENDENCIES . ' WHERE task_id = :task_id',
            ['task_id' => $taskId]
        );
    }
}
