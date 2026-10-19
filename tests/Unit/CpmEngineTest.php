<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Engine\CpmEngine;
use ksfraser\FrontAccounting\ProjectManagement\Engine\TaskDependencyGraph;
use PHPUnit\Framework\TestCase;

/**
 * CpmEngine — the 20 approved unit cases of UT-PM-006.
 *
 * One test method per UT id, in spec order, so the traceability gap between
 * UT-PM-006-cpm-unit.md and the executable suite is closed literally rather
 * than approximately. Every expectation is transcribed from the spec text,
 * NOT read back off the implementation: where the engine disagrees, the test
 * is red on purpose and documents the gap.
 *
 * CpmEngine is pure (arrays in, array out) so no DB double is needed here;
 * the FA-facing orchestration is covered by SchedulingServiceTest.
 *
 * @BABOK Related: UT-PM-006 (Approved), FR-PM-006-001
 */
class CpmEngineTest extends TestCase
{
    /** @var CpmEngine */
    private $engine;

    /**
     * @BABOK Related: FR-PM-006-001 — CPM engine behaviour
     */
    protected function setUp(): void
    {
        $this->engine = new CpmEngine();
    }

    // ---------------------------------------------------------------- helpers

    /**
     * A task row in the engine's task contract.
     *
     * @param string               $id
     * @param int                  $duration
     * @param array<string,mixed>  $extra
     * @return array<string,mixed>
      *
 * @BABOK Related: FR-PM-006-001 — CPM engine behaviour
 */
    private function task(string $id, int $duration, array $extra = []): array
    {
        return array_merge(['id' => $id, 'name' => $id, 'duration_days' => $duration], $extra);
    }

    /**
     * A zero-duration milestone row.
     *
     * @param string              $id
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
      *
 * @BABOK Related: FR-PM-006-001 — CPM engine behaviour
 */
    private function milestone(string $id, array $extra = []): array
    {
        return array_merge(['id' => $id, 'name' => $id, 'duration_days' => 0, 'is_milestone' => true], $extra);
    }

    /**
     * A precedence edge in the engine's dependency contract.
     *
     * @param string              $from Predecessor id
     * @param string              $to   Successor id
     * @param int                 $lag
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
      *
 * @BABOK Related: FR-PM-006-001 — CPM engine behaviour
 */
    private function edge(string $from, string $to, int $lag = 0, array $extra = []): array
    {
        return array_merge([
            'predecessor' => $from,
            'successor'   => $to,
            'type'        => 'fs',
            'lag'         => $lag,
        ], $extra);
    }

    /**
     * Assert the run succeeded and return it.
     *
     * @param array<string,mixed> $result
     * @return array<string,mixed>
      *
 * @BABOK Related: FR-PM-006-001 — CPM engine behaviour
 */
    private function assertOk(array $result): array
    {
        $this->assertTrue($result['ok'] ?? false, 'CpmEngine::run must return ok=true');

        return $result;
    }

    // ------------------------------------------- UT-006-001 forward pass (9)

    /**
     * UT-PM-006-001-001 — Two-task FS chain, A duration 8, B duration 5:
     * A es=0,ef=8; B es=8,ef=13; project_duration=13.
     *
     * @BABOK Related: UT-PM-006-001-001
     */
    public function testUt006001001FsChainForwardPass(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B')]
        ));

        $this->assertSame(0, $r['tasks']['A']['es']);
        $this->assertSame(8, $r['tasks']['A']['ef']);
        $this->assertSame(8, $r['tasks']['B']['es']);
        $this->assertSame(13, $r['tasks']['B']['ef']);
        $this->assertSame(13, $r['project_duration']);
    }

    /**
     * UT-PM-006-001-002 — FS + lag 3 (>=0 only): B es=11, ef=16; duration 16.
     *
     * @BABOK Related: UT-PM-006-001-002
     */
    public function testUt006001002FsWithLagThree(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B', 3)]
        ));

        $this->assertSame(11, $r['tasks']['B']['es']);
        $this->assertSame(16, $r['tasks']['B']['ef']);
        $this->assertSame(16, $r['project_duration']);
    }

    /**
     * UT-PM-006-001-003 — SS edge A->B, lag 2: B es = A.es+2 = 2 (ef=7).
     *
     * @BABOK Related: UT-PM-006-001-003
     */
    public function testUt006001003StartToStartEdgeHonoursPredecessorEs(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B', 2, ['type' => 'ss'])]
        ));

        $this->assertSame(2, $r['tasks']['B']['es']);
        $this->assertSame(7, $r['tasks']['B']['ef']);
    }

    /**
     * UT-PM-006-001-004 — FF gates the successor's FINISH: EF_succ >= EF_pred
     * + lag. With A(8), B(5), lag 2: EF_B = 10, so ES_B = 10 - 5 = 5.
     *
     * This case was GREEN-BUT-WRONG until the FF ruling (CPM-SPEC-REVIEW.md
     * §6): the approved text and the engine both wrote the predecessor's
     * *finish* candidate straight into the successor's *start*, which is five
     * days later than FF requires and derives B's float from a field FF never
     * gates.
     *
     * @BABOK Related: UT-PM-006-001-004
     */
    public function testUt006001004FinishToFinishEdgeGatesTheSuccessorFinish(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B', 2, ['type' => 'ff'])]
        ));

        // EF_B = A.ef(8) + lag(2) = 10; ES_B = EF_B - B.duration(5) = 5.
        $this->assertSame(10, $r['tasks']['B']['ef'], 'FF gates the successor finish');
        $this->assertSame(5, $r['tasks']['B']['es']);
        $this->assertSame(10, $r['project_duration']);
        $this->assertTrue(
            $r['tasks']['B']['critical'],
            'FF is the driving relation here, so B is on the critical path'
        );
    }

    /**
     * UT-PM-006-001-005 — SF: predecessor EF+lag-succ duration:
     * B es = A.ef+lag-5.
     *
     * @BABOK Related: UT-PM-006-001-005
     */
    public function testUt006001005StartToFinishEdgeSubtractsSuccessorDuration(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B', 2, ['type' => 'sf'])]
        ));

        // A.ef(8) + lag(2) - B.duration(5)
        $this->assertSame(5, $r['tasks']['B']['es']);
    }

    /**
     * UT-PM-006-001-006 — Milestone zero duration: ef==es.
     *
     * @BABOK Related: UT-PM-006-001-006
     */
    public function testUt006001006MilestoneHasZeroDurationAndEfEqualsEs(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->milestone('M')],
            [$this->edge('A', 'M')]
        ));

        $this->assertSame(8, $r['tasks']['M']['es']);
        $this->assertSame(8, $r['tasks']['M']['ef']);
        $this->assertSame(
            $r['tasks']['M']['es'],
            $r['tasks']['M']['ef'],
            'a milestone must satisfy ef == es'
        );
        $this->assertSame(0, $r['tasks']['M']['duration']);
    }

    /**
     * UT-PM-006-001-007 — start_no_earlier_than anchor raises ES.
     *
     * @BABOK Related: UT-PM-006-001-007
     */
    public function testUt006001007StartNoEarlierThanAnchorRaisesEs(): void
    {
        $anchor = 20;
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 4, ['constraint' => [['type' => 'start_no_earlier_than', 'date' => $anchor]]])],
            []
        ));

        $this->assertSame(20, $r['tasks']['A']['es'], 'the anchor must raise ES');
        $this->assertSame(24, $r['tasks']['A']['ef']);
        $this->assertSame(24, $r['project_duration']);
    }

    /**
     * UT-PM-006-001-008 — finish_no_earlier_than anchor raises EF.
     *
     * @BABOK Related: UT-PM-006-001-008
     */
    public function testUt006001008FinishNoEarlierThanAnchorRaisesEf(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 2, ['constraint' => [['type' => 'finish_no_earlier_than', 'date' => 10]]])],
            []
        ));

        $this->assertSame(10, $r['tasks']['A']['ef'], 'the anchor must raise EF');
        $this->assertSame(0, $r['tasks']['A']['es'], 'a finish anchor must not move ES');
    }

    /**
     * UT-PM-006-001-009 — Multiple-input merge: ES = max of incoming candidates.
     *
     * @BABOK Related: UT-PM-006-001-009
     */
    public function testUt006001009MergeTaskEsIsTheMaxIncomingCandidate(): void
    {
        // FS from A offers 8 (A.ef); SS from B offers 9 (B.es + lag 9).
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 2), $this->task('C', 1)],
            [
                $this->edge('A', 'C'),
                $this->edge('B', 'C', 9, ['type' => 'ss']),
            ]
        ));

        $this->assertSame(9, $r['tasks']['C']['es'], 'ES = max(8, 9) = 9');
        $this->assertSame(10, $r['tasks']['C']['ef']);
    }

    // ------------------------------------ UT-006-002 backward pass / slack (5)

    /**
     * UT-PM-006-002-001 — LS/LF anchor: critical A-B chain both slack 0.
     *
     * @BABOK Related: UT-PM-006-002-001
     */
    public function testUt006002001CriticalChainHasZeroSlackOnBothTasks(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B')]
        ));

        // A: lf = ls(B) = 8; ls = lf - 8 = 0; slack = 0 - 0 = 0.
        $this->assertSame(8, $r['tasks']['A']['lf']);
        $this->assertSame(0, $r['tasks']['A']['ls']);
        $this->assertSame(0, $r['tasks']['A']['slack'], 'a task on the critical chain has zero slack');
        $this->assertTrue($r['tasks']['A']['critical']);

        $this->assertSame(13, $r['tasks']['B']['lf']);
        $this->assertSame(8, $r['tasks']['B']['ls']);
        $this->assertSame(0, $r['tasks']['B']['slack']);
        $this->assertTrue($r['tasks']['B']['critical']);
    }

    /**
     * UT-PM-006-002-002 — Non-critical merge task slack > 0.
     *
     * B2 is a merge task (two predecessors) on the short branch: it must be
     * pulled forward only as far as its own two inputs allow, which leaves it
     * with float.
     *
     * @BABOK Related: UT-PM-006-002-002
     */
    public function testUt006002002NonCriticalMergeTaskHasPositiveSlack(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 10), $this->task('B1', 2), $this->task('B2', 3), $this->task('C', 1)],
            [
                $this->edge('A', 'C'),
                $this->edge('B1', 'C'),
                $this->edge('B2', 'C', 0, ['type' => 'ss']),
            ]
        ));

        $this->assertGreaterThan(
            0,
            $r['tasks']['B2']['slack'],
            'the short-branch merge task carries float'
        );
        $this->assertFalse($r['tasks']['B2']['critical']);
        $this->assertNotContains('B2', $r['critical']);
    }

    /**
     * UT-PM-006-002-003 — slack is TOTAL FLOAT (signed `ls−es`); critical iff
     * `0 <= slack <= 0.5`.
     *
     * The approved text originally read `slack=abs(ls−es)` and
     * `critical=slack<=0.5`. Both were ruled wrong (CPM-SPEC-REVIEW.md §2):
     * read literally, `abs()` contradicts `002-001` and `002-005` (with the
     * old negative floats it made a critical task look non-critical), and
     * `abs()` would turn a −3d constraint violation into a healthy-looking
     * +3d of float AND hide it from criticality. The test now asserts the
     * documented rule. On this network float is non-negative, so the value
     * assertion holds either way; what changed is that the test states the
     * contract instead of a vacuous equality.
     *
     * @BABOK Related: UT-PM-006-002-003
     */
    public function testUt006002003SlackIsSignedTotalFloatAndCriticalityIsAOneSidedBand(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B')]
        ));

        foreach ($r['tasks'] as $id => $row) {
            $this->assertSame(
                $row['ls'] - $row['es'],
                $row['slack'],
                "slack for $id must be the signed total float ls - es"
            );
            $this->assertGreaterThanOrEqual(
                0,
                $row['slack'],
                "total float for $id cannot be negative in an unconstrained acyclic network"
            );
            $this->assertSame(
                ($row['slack'] >= 0 && $row['slack'] <= CpmEngine::CRITICAL_TOLERANCE),
                $row['critical'],
                "criticality for $id must be 0 <= slack <= tolerance"
            );
        }
    }

    /**
     * UT-PM-006-002-004 — Milestone critical iff slack<=0.5.
     *
     * @BABOK Related: UT-PM-006-002-004
     */
    public function testUt006002004MilestoneCriticalityFollowsSlack(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->milestone('M')],
            [$this->edge('A', 'M')]
        ));

        $m = $r['tasks']['M'];
        $this->assertSame($m['ls'] - $m['es'], $m['slack']);
        $this->assertSame($m['slack'] >= 0 && $m['slack'] <= CpmEngine::CRITICAL_TOLERANCE, $m['critical']);
        $this->assertTrue($m['critical'], 'a milestone closing the critical chain is critical');
    }

    /**
     * UT-PM-006-002-005 — `critical` array task ids + project_duration correct.
     *
     * @BABOK Related: UT-PM-006-002-005
     */
    public function testUt006002005CriticalIdSetAndProjectDuration(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B')]
        ));

        $this->assertSame(13, $r['project_duration']);
        $this->assertEqualsCanonicalizing(
            ['A', 'B'],
            $r['critical'],
            'both tasks of the A-B chain are on the critical path'
        );
    }

    // ------------------------------------------------ UT-006-003 robustness (6)

    /**
     * UT-PM-006-003-001 — Self-dependency rejected (predecessor==successor)
     * -> not cycle.
     *
     * @BABOK Related: UT-PM-006-003-001
     */
    public function testUt006003001SelfDependencyIsRejectedAndIsNotACycle(): void
    {
        $r = $this->engine->run([$this->task('A', 3)], [$this->edge('A', 'A')]);

        $this->assertFalse($r['ok'] ?? true, 'a self-dependency must be rejected');
        $this->assertSame(
            [],
            $r['cycle'] ?? [],
            'a self-dependency is a validation error, not a precedence cycle'
        );
    }

    /**
     * UT-PM-006-003-002 — Cycle A->B->C->A: ok=false, `cycle` lists path
     * [A,B,C,(A)].
     *
     * @BABOK Related: UT-PM-006-003-002
     */
    public function testUt006003002CycleIsReportedAsAClosedPath(): void
    {
        $r = $this->engine->run(
            [$this->task('A', 1), $this->task('B', 1), $this->task('C', 1)],
            [$this->edge('A', 'B'), $this->edge('B', 'C'), $this->edge('C', 'A')]
        );

        $this->assertFalse($r['ok'] ?? true, 'a three-node cycle must return ok=false');
        $this->assertIsArray($r['cycle'] ?? null);

        $cycle = $r['cycle'];
        foreach (['A', 'B', 'C'] as $id) {
            $this->assertContains($id, $cycle, "cycle path must name $id");
        }
        $last = count($cycle) - 1;
        $this->assertSame(
            $cycle[0],
            $cycle[$last],
            'the cycle path must close back on its first node: [A,B,C,(A)]'
        );
    }

    /**
     * UT-PM-006-003-003 — Lag negative -> recorded as-is (allowed; anchor math
     * uses raw).
     *
     * @BABOK Related: UT-PM-006-003-003
     */
    public function testUt006003003NegativeLagIsUsedRaw(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [$this->edge('A', 'B', -2)]
        ));

        $this->assertSame(6, $r['tasks']['B']['es'], 'B es = A.ef(8) + raw lag(-2)');
        $this->assertSame(11, $r['tasks']['B']['ef']);
        $this->assertSame(11, $r['project_duration']);
    }

    /**
     * UT-PM-006-003-004 — Empty tasks -> ok=true, duration 0, empty critical.
     *
     * @BABOK Related: UT-PM-006-003-004
     */
    public function testUt006003004EmptyTaskSetIsValidAndZeroLength(): void
    {
        $r = $this->assertOk($this->engine->run([], []));

        $this->assertSame(0, $r['project_duration']);
        $this->assertSame([], $r['critical']);
        $this->assertSame([], $r['tasks']);
    }

    /**
     * UT-PM-006-003-005 — Constraint key variants: id/task_id,
     * pred/successor, lag/lag_days, type default fs.
     *
     * @BABOK Related: UT-PM-006-003-005
     */
    public function testUt006003005AcceptsAllKeyVariantsAndDefaultsTypeToFs(): void
    {
        // (a) id / predecessor / successor / lag
        $r = $this->assertOk($this->engine->run(
            [['id' => 'A', 'duration_days' => 8], ['id' => 'B', 'duration_days' => 5]],
            [['predecessor' => 'A', 'successor' => 'B', 'lag' => 4]]
        ));
        $this->assertSame(12, $r['tasks']['B']['es'], 'id/predecessor/successor/lag variant');

        // (b) task_id / predecessor_id / task_id / lag_days
        $r = $this->assertOk($this->engine->run(
            [['task_id' => 'A', 'duration_days' => 8], ['task_id' => 'B', 'duration_days' => 5]],
            [['predecessor_id' => 'A', 'task_id' => 'B', 'lag_days' => 4]]
        ));
        $this->assertSame(12, $r['tasks']['B']['es'], 'task_id/predecessor_id/lag_days variant');

        // (c) type defaults to fs when absent (same numbers as SS lag 4)
        $r = $this->assertOk($this->engine->run(
            [['id' => 'A', 'duration_days' => 8], ['id' => 'B', 'duration_days' => 5]],
            [['predecessor' => 'A', 'successor' => 'B', 'lag' => 4]]
        ));
        $this->assertSame(12, $r['tasks']['B']['es'], 'absent type must default to FS, not SS');

        // (d) an unrecognised type is coerced to FS rather than rejected
        $r = $this->assertOk($this->engine->run(
            [['id' => 'A', 'duration_days' => 8], ['id' => 'B', 'duration_days' => 5]],
            [$this->edge('A', 'B', 4, ['type' => 'nonsense'])]
        ));
        $this->assertSame(12, $r['tasks']['B']['es']);
    }

    /**
     * UT-PM-006-003-006 — Missing predecessor/successor references skipped
     * (not error).
     *
     * @BABOK Related: UT-PM-006-003-006
     */
    public function testUt006003006DanglingDependencyReferencesAreSkipped(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 8), $this->task('B', 5)],
            [
                $this->edge('A', 'X'),   // successor not in the task set
                $this->edge('Y', 'B'),   // predecessor not in the task set
                $this->edge('A', 'B'),   // the only real edge
            ]
        ));

        $this->assertSame(8, $r['tasks']['B']['es'], 'only the resolvable edge constrains B');
        $this->assertSame(13, $r['project_duration']);
        $this->assertArrayNotHasKey('X', $r['tasks']);
        $this->assertArrayNotHasKey('Y', $r['tasks']);
    }

    // ----------------------------------- defects found beyond the 20 cases
    // The cases below are NOT part of UT-PM-006. They are recorded because the
    // 20 approved cases do not reach them and each is a live defect.

    /**
     * The `cycle` payload must name the nodes actually on a loop.
     *
     * The engine derives it from "every node missing from the topological
     * order", so an acyclic sibling is reported as part of the cycle too.
     *
     * @BABOK Related: UT-PM-006-006-001, FR-PM-006-001
     */
    public function testCycleReportExcludesAcyclicTasks(): void
    {
        // A<->B is the loop; E merely hangs off it and is acyclic.
        $r = $this->engine->run(
            [$this->task('A', 1), $this->task('B', 1), $this->task('E', 1)],
            [$this->edge('A', 'B'), $this->edge('B', 'A'), $this->edge('A', 'E')]
        );

        $this->assertFalse($r['ok'] ?? true);
        $this->assertNotContains('E', $r['cycle'], 'E is not on a loop and must not be reported');
    }

    /**
     * A calendar-date anchor from a real `fa_pm_tasks.constraint_date` column.
     *
     * @BABOK Related: UT-PM-006-006-002, FR-PM-006-001
     */
    public function testCalendarDateAnchorDoesNotExplodeIntoDaysSinceEpoch(): void
    {
        $r = $this->assertOk($this->engine->run(
            [$this->task('A', 4, ['constraint' => [['type' => 'start_no_earlier_than', 'date' => '2026-01-01']]])],
            []
        ));

        $this->assertSame(
            4,
            $r['tasks']['A']['ef'],
            'a Y-m-d anchor must be read as a schedule offset, not floor(ts/86400)'
        );
    }

    // ------------------------- UT-006-006-003/004: the write-time cycle guard
    // FR-PM-006-003: CpmEngine::detectCycle() is the guard's entry point, so
    // it has to answer the same question run() answers, on a graph the
    // persistence service builds from rows rather than from a task table.

    /**
     * UT-PM-006-006-003 — detectCycle() returns a genuine, CLOSED cycle path.
     *
     * Not the set difference "everything the topological sort could not
     * place": the returned traversal must name only the nodes on a loop and
     * must close back on its first node so a caller can render where the loop
     * closes.
     *
     * @BABOK Related: UT-PM-006-006-003, FR-PM-006-003
     */
    public function testDetectCycleReturnsAClosedPathOnTheRealCycleOnly(): void
    {
        $g = new TaskDependencyGraph();
        // A->B and B->A is the loop; E only hangs off it.
        $g->addEdge('A', 'B');
        $g->addEdge('B', 'A');
        $g->addEdge('A', 'E');

        $cycle = (new CpmEngine())->detectCycle($g);

        $this->assertNotSame([], $cycle, 'A<->B is a cycle');
        $this->assertNotContains('E', $cycle, 'E is not on a loop');
        $this->assertSame(
            $cycle[0],
            $cycle[count($cycle) - 1],
            'the cycle path must close back on its first node'
        );
        $this->assertEqualsCanonicalizing(
            ['A', 'B'],
            array_values(array_unique($cycle)),
            'the reported traversal must name only the nodes actually on the loop'
        );
    }

    /**
     * UT-PM-006-006-004 — detectCycle() accepts a real acyclic graph.
     *
     * The guard's other half: if this returned a non-empty path for a diamond
     * network, no dependency could ever be written.
     *
     * @BABOK Related: UT-PM-006-006-004, FR-PM-006-003
     */
    public function testDetectCycleAcceptsAnAcyclicDiamond(): void
    {
        $g = new TaskDependencyGraph();
        $g->addEdge('A', 'B');
        $g->addEdge('A', 'C');
        $g->addEdge('B', 'D');
        $g->addEdge('C', 'D');
        $g->addEdge('D', 'E');

        $this->assertSame([], (new CpmEngine())->detectCycle($g));
        $this->assertSame([], (new CpmEngine())->detectCycle(new TaskDependencyGraph()));
    }
}