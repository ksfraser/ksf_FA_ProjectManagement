<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository;
use ksfraser\FrontAccounting\ProjectManagement\Engine\CpmEngine;
use ksfraser\FrontAccounting\ProjectManagement\Engine\TaskDependencyGraph;
use ksfraser\FrontAccounting\Common\Traits\WorkflowHooksTrait;

/**
 * Task dependency workflow — validation, cycle guard and persistence for the
 * CPM precedence edges (task_dependencies).
 *
 * This is the service that backs BR-003-002 (predecessor edges) and the
 * scheduling engine's adjacency feed. It deliberately reuses the pure
 * graph/cycle logic from the 'CPM diagnostic facility' (CpmEngine) — the
 * same class that computes the forward/backward pass — so the invariant
 * "a PM dependency graph is always a DAG" is enforced in ONE place.
 *
 * It fires workflow hooks (pm_task_dependency_before_save etc.) so FA's
 * hook system stays the single inter-module communication channel.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: FR-PM-006-003, BR-PM-006, BR-003-002 (task dependencies),
 *                  BR-003-003 (milestones)
 */
class TaskDependencyService
{
    use WorkflowHooksTrait;

    const VALID_TYPES = ['FS', 'SS', 'FF', 'SF'];

    /** @var TaskDependencyRepository */
    private $deps;

    /** @var TaskRepository */
    private $tasks;

    /** @var CpmEngine */
    private $engine;

    public function __construct(
        ?TaskDependencyRepository $deps = null,
        ?TaskRepository $tasks = null,
        ?CpmEngine $engine = null
    ) {
        $this->deps   = $deps   ?? new TaskDependencyRepository();
        $this->tasks  = $tasks  ?? new TaskRepository();
        $this->engine = $engine ?? new CpmEngine();
        $this->registerWorkflowType('task_dependency', 'pm_task_dependency');
    }

    /**
     * Persist a precedence edge. Runs the workflow hooks and refuses writes
     * that would close a cycle (the engine's required pre-condition).
     *
     * The row scan that backs the duplicate check and the cycle guard is
     * performed once and shared: both answers come from the same persisted
     * graph, and two full-table reads per insert is one too many.
     *
     * @param array<string,mixed> $dependency
     * @return string The new dependency id.
     * @throws \InvalidArgumentException When the edge is malformed, a
     *         duplicate, or would close a precedence cycle.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-003
     */
    public function add(array $dependency): string
    {
        $data = [
            'task_id'         => $dependency['task_id'],
            'predecessor_id'  => $dependency['predecessor_id'],
            'dependency_type' => $dependency['dependency_type'] ?? 'FS',
            'lag'             => $dependency['lag'] ?? 0.00,
        ];
        $this->validate($data);
        $this->fireWorkflowHook('task_dependency', 'before_save', $data);

        $rows = $this->deps->all();
        foreach ($rows as $row) {
            if ($row['task_id'] === $data['task_id']
                && $row['predecessor_id'] === $data['predecessor_id']) {
                throw new \InvalidArgumentException(
                    'Duplicate dependency: ' . $data['task_id']
                    . ' -> ' . $data['predecessor_id']
                );
            }
        }
        $this->guardCycle($data, $rows);

        $id = $this->deps->insert($data);
        $this->fireWorkflowHook('task_dependency', 'after_save', $data);
        return $id;
    }

    /**
     * Remove a precedence edge.
     *
     * @param string $dependencyId
     * @return void
     *
     * @BABOK Related: FR-PM-006-003
     */
    public function deleteDependency(string $dependencyId): void
    {
        $this->fireWorkflowHook('deleteDependency', 'before_delete', ['dependency_id' => $dependencyId]);
        $this->deps->delete($dependencyId);
        $this->fireWorkflowHook('deleteDependency', 'after_delete', ['dependency_id' => $dependencyId]);
    }

    /**
     * Validate dependency field constraints before write.
     *
     * @param array<string,mixed> $data
     * @return void
     * @throws \InvalidArgumentException
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-001
     */
    private function validate(array $data): void
    {
        if (empty($data['task_id']) || empty($data['predecessor_id'])) {
            throw new \InvalidArgumentException('Dependency requires task and predecessor ids.');
        }
        if ($data['task_id'] === $data['predecessor_id']) {
            throw new \InvalidArgumentException('A task cannot depend on itself.');
        }
        if (!in_array($data['dependency_type'], self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException(
                'Unsupported dependency type: ' . $data['dependency_type']
            );
        }
    }

    /**
     * Prevent a dependency write from introducing a cycle. Builds the DAG
     * from the current rows plus the new edge and asks CpmEngine to detect it
     * — the same tri-colour search run() uses, so there is one definition of
     * "cycle" in the module.
     *
     * The thrown message carries the closed cycle path, which is the only way
     * a caller can tell the user where the loop actually closes.
     *
     * @param array<string,mixed>                $newEdge
     * @param array<int,array<string,mixed>>     $rows Persisted rows already read.
     *
     * @return void
     * @throws \InvalidArgumentException When the new edge would close a loop.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-002
     */
    private function guardCycle(array $newEdge, array $rows = []): void
    {
        $graph = $this->currentGraph($rows);
        $graph->addEdge($newEdge['predecessor_id'], $newEdge['task_id']);

        $cycle = $this->engine->detectCycle($graph);
        if ($cycle !== []) {
            throw new \InvalidArgumentException(
                'Dependency would create a cycle: ' . implode(' -> ', $cycle)
            );
        }
    }

    /**
     * The current precedence DAG, from the persisted rows.
     *
     * @param array<int,array<string,mixed>> $rows Rows already read by add().
     *
     * @return TaskDependencyGraph
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-005
     */
    private function currentGraph(array $rows = []): TaskDependencyGraph
    {
        if ($rows === []) {
            $rows = $this->deps->all();
        }
        $graph = new TaskDependencyGraph();
        foreach ($rows as $row) {
            $graph->addEdge($row['predecessor_id'], $row['task_id']);
        }

        return $graph;
    }
}
