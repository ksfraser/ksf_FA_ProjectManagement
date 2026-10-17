<?php

declare(strict_types=1);

namespace Ksfraser\FrontAccounting\ProjectManagement\Service;

use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository;
use ksfraser\FrontAccounting\ProjectManagement\Engine\CpmEngine;
use ksfraser\FrontAccounting\ProjectManagement\Engine\TaskDependencyGraph;
use Ksfraser\Common\Traits\WorkflowHooksTrait;

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
 * @BABOK Related: BR-003-002 (task dependencies), BR-003-003 (milestones)
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
     * @param array<string,mixed> $dependency
     * @return string
     */
    public function createDependency(array $dependency): string
    {
        $data = [
            'task_id'         => $dependency['task_id'],
            'predecessor_id'  => $dependency['predecessor_id'],
            'dependency_type' => $dependency['dependency_type'] ?? 'FS',
            'lag'             => $dependency['lag'] ?? 0.00,
        ];
        $this->validate($data);
        $this->fireWorkflowHook('createDependency', 'before_save', $data);

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
        $this->guardCycle($data);

        $id = $this->deps->insert($data);
        $this->fireWorkflowHook('createDependency', 'after_save', $data);
        return $id;
    }

    /**
     * Remove a precedence edge.
     *
     * @param string $dependencyId
     * @return void
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
     * from the current rows + the new edge and asks CpmEngine to detect it.
     *
     * @param array<string,mixed> $newEdge
     * @return void
     * @throws \InvalidArgumentException When the new edge would close a loop.
     */
    private function guardCycle(array $newEdge): void
    {
        $graph = $this->currentGraph();
        $graph->addEdge($newEdge['predecessor_id'], $newEdge['task_id']);
        if ($this->engine->detectCycle($graph)) {
            throw new \InvalidArgumentException(
                'Dependency would create a cycle: '
                . $newEdge['task_id'] . ' -> ' . $newEdge['predecessor_id']
            );
        }
    }

    /**
     * The current precedence DAG (existing rows only).
     *
     * @return TaskDependencyGraph
     */
    private function currentGraph(): TaskDependencyGraph
    {
        $graph = new TaskDependencyGraph();
        foreach ($this->deps->all() as $row) {
            $graph->addEdge($row['predecessor_id'], $row['task_id']);
        }
        return $graph;
    }
}
