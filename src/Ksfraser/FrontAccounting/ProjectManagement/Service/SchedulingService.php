<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ProjectManagement\Service;

use Ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use Ksfraser\FrontAccounting\ProjectManagement\Engine\CpmEngine;
use Ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use Ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository;
use Ksfraser\FrontAccounting\Common\Traits\WorkflowHooksTrait;

/**
 * SchedulingService — CPM forward/backward pass orchestration + persistence.
 *
 * Reads the task dictionary + dependency edges, runs the pure CpmEngine, and
 * persists the computed ES/EF/LS/LF/slack/critical back onto the tasks rows
 * so the schedule tab, calendar emitter and gantt all read one source of
 * truth.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: BR-003 (Scheduling / CPM)
 * @UML Note: orchestration service — CpmEngine (pure) + TaskRepository DAO
 */
class SchedulingService
{
    use WorkflowHooksTrait;

    /** @var TaskRepository */
    private $tasks;

    /** @var TaskDependencyRepository */
    private $deps;

    /** @var CpmEngine */
    private $engine;

    public function __construct(
        ?TaskRepository $tasks = null,
        ?TaskDependencyRepository $deps = null,
        ?CpmEngine $engine = null
    ) {
        $this->tasks  = $tasks  ?? new TaskRepository();
        $this->deps   = $deps   ?? new TaskDependencyRepository();
        $this->engine = $engine ?? new CpmEngine();
        $this->registerWorkflowType('schedule', 'pm_schedule');
    }

    /**
     * Run the CPM pass for a project and persist computed scheduling fields.
     *
     * @param string $projectId
     * @return array{
     *     ok: bool,
     *     cycle?: string[],
     *     tasks?: array<string,array<string,mixed>>,
     *     project_duration?: int,
     *     critical?: string[]
     * }
     */
    public function schedule(string $projectId): array
    {
        $taskRows = $this->tasks->findPage(1, 1000, ['project_id' => $projectId]);
        $depRows  = $this->deps->findAllByProject($projectId);

        $normalized = $this->normalizeTasks($taskRows);
        $edges      = $this->normalizeEdges($depRows);

        $result = $this->engine->run($normalized, $edges);
        if (empty($result['ok'])) {
            return ['ok' => false, 'cycle' => $result['cycle'] ?? []];
        }

        $payload = ['project_id' => $projectId, 'result' => $result];
        $this->fireWorkflowHook('schedule', 'schedule_after', $payload);

        $this->persist($projectId, $result);

        $payload['persisted'] = true;
        $this->fireWorkflowHook('schedule', 'schedule_saved', $payload);

        return $result;
    }

    /**
     * Map repository rows onto the engine's task contract.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function normalizeTasks(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $duration = (float) ($row['estimated_hours'] ?? 0.0);
            $out[] = [
                'task_id'     => $row['task_id'],
                'name'        => $row['name'] ?? '',
                'duration'    => $duration > 0 ? (int) round($duration / 8.0) : 1,
                'is_milestone' => ((int) ($row['is_milestone'] ?? 0)) === 1,
                'constraint'  => $this->constraintFromRow($row),
            ];
        }
        return $out;
    }

    /**
     * Map repository edges onto the engine's dependency contract.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function normalizeEdges(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'task_id'        => $row['task_id'],
                'predecessor_id' => $row['predecessor_id'],
                'dependency_type'=> strtolower($row['dependency_type'] ?? 'fs'),
                'lag_days'       => (float) ($row['lag'] ?? 0.0),
            ];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $row
     * @return array{type:string,date?:string}|null
     */
    private function constraintFromRow(array $row): ?array
    {
        $constraint = $row['constraint_type'] ?? null;
        if (empty($constraint)) {
            return null;
        }
        $map = [
            'start_no_earlier_than' => 'start_no_earlier_than',
            'start_no_later_than'   => 'start_no_later_than',
            'must_start_on'         => 'must_start_on',
            'finish_no_earlier_than'=> 'finish_no_earlier_than',
            'finish_no_later_than'  => 'finish_no_later_than',
            'must_finish_on'        => 'must_finish_on',
        ];
        $type = $map[$constraint] ?? null;
        if ($type === null) {
            return null;
        }
        $date = $row['constraint_date'] ?? null;
        if (empty($date)) {
            return ['type' => $type];
        }
        return ['type' => $type, 'date' => $date];
    }

    /**
     * Persist ES/EF/LS/LF/slack/critical back onto the task dictionary.
     *
     * @param string $projectId
     * @param array<string,mixed> $result
     * @return void
     */
    private function persist(string $projectId, array $result): void
    {
        foreach ($result['tasks'] ?? [] as $taskId => $sched) {
            $this->tasks->updateSchedule($taskId, [
                'es' => $sched['es'] ?? null,
                'ef' => $sched['ef'] ?? null,
                'ls' => $sched['ls'] ?? null,
                'lf' => $sched['lf'] ?? null,
                'slack'         => $sched['slack'] ?? 0.00,
                'is_critical'   => !empty($sched['critical']) ? 1 : 0,
            ]);
        }
    }
}
