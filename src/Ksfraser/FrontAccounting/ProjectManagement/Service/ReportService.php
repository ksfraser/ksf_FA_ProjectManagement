<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\CommonDb\Query\QueryBuilder;
use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ActivityLogRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectOrderRepository;

/**
 * ReportService — dashboard stats + cross-project aggregates.
 *
 * All aggregates go through the shared query-builder (QueryBuilder) and
 * DbConnectionInterface — FA runtime translation to native db_* is handled by
 * FaDbAdapter, exactly like the rest of the module's DAO layer.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db
 * @BABOK Related: BR-012, FR-PM-009/010 (revenue)
 */
class ReportService
{
    /** @var ProjectRepository */
    private $projects;

    /** @var TaskRepository */
    private $tasks;

    /** @var ActivityLogRepository */
    private $activity;

    /** @var ProjectOrderRepository */
    private $orders;

    /** @var \ksfraser\CommonDb\Contract\DbConnectionInterface */
    private $db;

    public function __construct(
        ?ProjectRepository $projects = null,
        ?TaskRepository $tasks = null,
        ?ActivityLogRepository $activity = null,
        ?ProjectOrderRepository $orders = null,
        ?\ksfraser\CommonDb\Contract\DbConnectionInterface $db = null
    ) {
        $this->projects = $projects ?? new ProjectRepository($db);
        $this->tasks    = $tasks    ?? new TaskRepository($db);
        $this->activity = $activity ?? new ActivityLogRepository($db);
        $this->orders   = $orders   ?? new ProjectOrderRepository($db);
        $this->db       = $db       ?? \ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema::adapter();
    }

    /**
     * Key counts for the dashboard stat cards.
     *
     * @return array<string, int|float>
     */
    public function dashboardStats(): array
    {
        $overdue = $this->countOverdueTasks();

        return [
            'total_projects'    => $this->projects->countAll(),
            'active_projects'   => $this->projects->countAll(['status' => 'Active']),
            'completed_projects'=> $this->projects->countAll(['status' => 'Completed']),
            'total_tasks'       => $this->tasks->countAll(),
            'pending_tasks'     => $this->tasks->countAll(['status' => 'Not Started']),
            'in_progress_tasks' => $this->tasks->countAll(['status' => 'In Progress']),
            'overdue_tasks'     => $overdue,
            'total_revenue'     => $this->revenueTotal(),
        ];
    }

    /**
     * Overdue tasks (end_date < today, not Completed/Cancelled).
     *
     * @return int
     */
    public function countOverdueTasks(): int
    {
        $qb = new QueryBuilder();
        $qb->select('COUNT(*)')
            ->from(Schema::T_TASKS)
            ->where('end_date IS NOT NULL')
            ->where('end_date < :today', ['today' => date('Y-m-d')])
            ->where('status <> :s1', ['s1' => 'Completed'])
            ->where('status <> :s2', ['s2' => 'Cancelled']);
        return (int) $this->db->fetchScalar($qb->toSql(), $qb->getParams(), 0);
    }

    /**
     * Recognized revenue across all projects.
     *
     * @return float
     */
    public function revenueTotal(): float
    {
        $qb = new QueryBuilder();
        $qb->select('COALESCE(SUM(revenue_amount), 0) AS total')
            ->from(Schema::T_REVENUE);
        return (float) $this->db->fetchScalar($qb->toSql(), $qb->getParams(), 0);
    }

    /**
     * Budget vs recognized revenue per project.
     *
     * @return array<int, array<string, mixed>>
     */
    public function budgetVsRevenue(): array
    {
        $qb = new QueryBuilder();
        $qb->select([
                'p.project_id',
                'p.name',
                'p.budget',
                'COALESCE(SUM(r.revenue_amount), 0) AS revenue',
            ])
            ->from(Schema::T_PROJECTS . ' p')
            ->join('LEFT JOIN ' . Schema::T_REVENUE . ' r ON r.project_id = p.project_id')
            ->groupBy('p.project_id')
            ->groupBy('p.name')
            ->groupBy('p.budget')
            ->orderBy('p.budget DESC');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * Estimated vs actual hours per project.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hoursByProject(): array
    {
        $qb = new QueryBuilder();
        $qb->select([
                'project_id',
                'COALESCE(SUM(estimated_hours), 0) AS estimated_hours',
                'COALESCE(SUM(actual_hours), 0) AS actual_hours',
                'COUNT(*) AS task_count',
            ])
            ->from(Schema::T_TASKS)
            ->groupBy('project_id')
            ->orderBy('project_id');
        return $this->db->fetchAll($qb->toSql(), $qb->getParams());
    }

    /**
     * Most recent activity-log entries (dashboard feed).
     *
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function recentActivity(int $limit = 8): array
    {
        return $this->activity->recent($limit);
    }

    /**
     * Revenue summary for a single project (uses the ported order DAO).
     *
     * @param string $projectId
     * @return array<string, mixed>
     */
    public function revenueSummaryByProject(string $projectId): array
    {
        return $this->orders->getRevenueSummaryByProject($projectId);
    }
}