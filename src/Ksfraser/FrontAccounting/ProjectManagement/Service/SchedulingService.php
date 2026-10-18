<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Engine\CpmEngine;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository;
use ksfraser\FrontAccounting\Common\Traits\WorkflowHooksTrait;

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
 * @BABOK Related: FR-PM-006-002, BR-PM-006, BR-003 (Scheduling / CPM)
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
     * The signature is deliberately one argument. The service reads the task
     * rows and the precedence edges for `$projectId` from its injected
     * repositories, so it always schedules the graph that is actually
     * persisted. The original draft of FR-PM-006-002 had
     * `schedule($projectId, $startDate, $tasks, $dependencies[, $endDate])`,
     * which let a caller hand in a task set that had already drifted from the
     * database — the schedule would then be computed over a graph nothing else
     * in the system believes in, and the persisted ES/EF/LS/LF would silently
     * disagree with the task list on the next page load.
     *
     * Two hooks fire, both under the `pm_schedule` prefix:
     *
     * - `pm_schedule_schedule_after` — the schedule is COMPUTED, nothing
     *   written. A caller that only wants to preview or explain a schedule
     *   listens here and pays no writes.
     * - `pm_schedule_schedule_saved` — the same payload plus
     *   `persisted: true`. This is the one to listen to in order to react to a
     *   committed schedule.
     *
     * The split is the reason there are two. A single post-write hook would
     * force a caller that only wants to render a preview to write to the
     * database to get its numbers.
     *
     * @param string $projectId
     *
     * @return array{
     *     ok: bool,
     *     cycle?: string[],
     *     tasks?: array<string,array<string,mixed>>,
     *     project_duration?: int,
     *     critical?: string[]
     * }
     *
     * @BABOK Related: FR-PM-006-002, UT-PM-006-004-004
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

        // duration/critical/ok are hoisted to the top level of BOTH payloads,
        // not just the post-write one: a listener has to be able to explain
        // the schedule without first digging the full task table out of
        // `result`. `result` itself is retained so a caller that needs per-task
        // ES/EF/LS/LF still has one place to read it from.
        $payload = [
            'project_id' => $projectId,
            'result'     => $result,
            'ok'         => true,
            'duration'   => $result['project_duration'] ?? 0,
            'critical'   => $result['critical'] ?? [],
        ];
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
