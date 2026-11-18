<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Engine;

/**
 * The minimum precedence graph the two CPM collaborators actually share.
 *
 * Deliberately minimal and deliberately not a second CPM engine: it holds
 * edges and hands out adjacency, and nothing else. Two callers need exactly
 * that, and no more:
 *
 * - `TaskDependencyService::currentGraph()` builds one from the persisted
 *   `task_dependencies` rows so it can test a candidate write before it lands.
 * - `CpmEngine::detectCycle()` walks one to answer "is this still acyclic?".
 *
 * Keeping the type this small is what lets the cycle algorithm stay in one
 * place (CpmEngine's tri-colour DFS). A richer graph type — one that also
 * computed forward/backward passes, held durations or resolved constraints —
 * would drag the engine's responsibilities into the persistence layer and put
 * a second implementation of the schedule within reach of shipping.
 *
 * It carries no durations and no relation types on purpose: a cycle is a
 * purely topological property, so FS/SS/FF/SF and lag are irrelevant to it and
 * are deliberately dropped when rows are loaded.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @BABOK Related: FR-PM-006-003, BR-PM-006
 * @UML Note: ProjectDcs/003-Scheduling/ARCH-PM-006-standalone-cpm-package.md
 */
class TaskDependencyGraph
{
    /**
     * Adjacency list: task id => ids of its successors.
     *
     * A node with no outgoing edge is present with an empty list, so
     * `nodes()` is the real vertex set and not just "the tasks that happen to
     * be predecessors".
     *
     * @var array<string,string[]>
     */
    private $successors = [];

    /**
     * Add one predecessor -> successor edge.
     *
     * Re-adding an edge already present is a no-op rather than an error: the
     * cycle guard deliberately folds the candidate edge into a graph that may
     * already contain it, and a parallel duplicate says nothing about
     * acyclicity.
     *
     * @param string $from Predecessor task id.
     * @param string $to   Successor task id.
     *
     * @return void
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-002
     */
    public function addEdge(string $from, string $to): void
    {
        if (!isset($this->successors[$from])) {
            $this->successors[$from] = [];
        }
        if (!isset($this->successors[$to])) {
            $this->successors[$to] = [];
        }
        if (!in_array($to, $this->successors[$from], true)) {
            $this->successors[$from][] = $to;
        }
    }

    /**
     * Declare a task that has no edges yet.
     *
     * @param string $id Task id.
     *
     * @return void
     *
     * @BABOK Related: FR-PM-006-003
     */
    public function addNode(string $id): void
    {
        if (!isset($this->successors[$id])) {
            $this->successors[$id] = [];
        }
    }

    /**
     * Every task id in the graph, in insertion order.
     *
     * @return string[]
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-006-003
     */
    public function nodes(): array
    {
        return array_keys($this->successors);
    }

    /**
     * The successors of one task; [] when it has none or is unknown.
     *
     * @param string $id Task id.
     *
     * @return string[]
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-006-003
     */
    public function successorsOf(string $id): array
    {
        return $this->successors[$id] ?? [];
    }

    /**
     * @param string $id Task id.
     * @return bool
     *
     * @BABOK Related: FR-PM-006-003
     */
    public function hasNode(string $id): bool
    {
        return isset($this->successors[$id]);
    }
}