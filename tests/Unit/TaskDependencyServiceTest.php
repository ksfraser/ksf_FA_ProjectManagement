<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use Ksfraser\Tests\ChildPhpRunner;
use PHPUnit\Framework\TestCase;

/**
 * TaskDependencyService — FR-PM-006-003 self-reference and cycle guards.
 *
 * The class cannot be loaded in-process (see testServiceClassIsLoadable), so
 * every scenario runs in a child process. That keeps each acceptance case an
 * independent, individually readable red test instead of one fatal that takes
 * the whole suite down.
 *
 * @BABOK Related: FR-PM-006-003, BR-PM-006
 */
class TaskDependencyServiceTest extends TestCase
{
    /**
     * FR-PM-006-003 — the service must be usable at all.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-007
     */
    public function testServiceClassIsLoadable(): void
    {
        $probe = ChildPhpRunner::run(
            "class_exists('ksfraser\\FrontAccounting\\ProjectManagement\\Service\\TaskDependencyService');"
        );

        $this->assertSame(
            0,
            $probe['exit'],
            'TaskDependencyService must be loadable: ' . ChildPhpRunner::diagnostic($probe['output'])
        );
    }

    /**
     * UT-PM-006-005-005 — the cycle guard delegates to
     * CpmEngine::detectCycle(TaskDependencyGraph). Neither collaborator existed
     * in the shipped tree; both are built now.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-005
     */
    public function testCycleGuardCollaboratorsExist(): void
    {
        $engine = new \ReflectionClass(\ksfraser\FrontAccounting\ProjectManagement\Engine\CpmEngine::class);

        $this->assertTrue(
            $engine->hasMethod('detectCycle'),
            'TaskDependencyService::guardCycle() calls CpmEngine::detectCycle(), which does not exist'
        );
        $this->assertTrue(
            class_exists('ksfraser\FrontAccounting\ProjectManagement\Engine\TaskDependencyGraph'),
            'TaskDependencyService::currentGraph() type-hints TaskDependencyGraph, which does not exist'
        );
    }

    /**
     * TaskDependencyService::currentGraph() and the duplicate check both scan
     * UT-PM-006-005-006 — `$this->deps->all()`, a method the repository did
     * not define.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-006
     */
    public function testRepositoryExposesTheRowScanTheGuardDependsOn(): void
    {
        $repo = new \ReflectionClass(
            \ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository::class
        );

        $this->assertTrue(
            $repo->hasMethod('all'),
            'TaskDependencyService calls TaskDependencyRepository::all(), which does not exist'
        );
    }

    /**
     * UT-PM-006-005-001 / FR-PM-006-003 — self-dependency rejected by
     * validation (no write).
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-001
     */
    public function testSelfDependencyIsRejectedWithoutAWrite(): void
    {
        $probe = ChildPhpRunner::runJson(<<<'PHP'
$GLOBALS['__fa_select_result'] = [];
$GLOBALS['__fa_sql_log'] = [];
$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\TaskDependencyService();
$threw = null;
try {
    $service->add(['task_id' => 'TSK-0001', 'predecessor_id' => 'TSK-0001']);
} catch (\InvalidArgumentException $e) {
    $threw = $e->getMessage();
}
$RESULT = ['threw' => $threw, 'writes' => $GLOBALS['__fa_sql_log']];
PHP);

        $this->assertSame(0, $probe['exit'], ChildPhpRunner::diagnostic($probe['output']));
        $this->assertStringContainsString('itself', (string) $probe['data']['threw']);
        $this->assertSame([], $probe['data']['writes'], 'nothing may be written');
    }

    /**
     * UT-PM-006-005-002 / FR-PM-006-003 — a back-edge that closes a cycle is
     * rejected (no write).
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-002
     */
    public function testBackEdgeClosingACycleIsRejectedWithoutAWrite(): void
    {
        $probe = ChildPhpRunner::runJson(<<<'PHP'
// Existing edges: TSK-0001 -> TSK-0002. Adding TSK-0001's predecessor
// TSK-0002 would close TSK-0001 -> TSK-0002 -> TSK-0001.
$GLOBALS['__fa_select_result'] = [
    ['dependency_id' => 'DEP-0001', 'task_id' => 'TSK-0002', 'predecessor_id' => 'TSK-0001'],
];
$GLOBALS['__fa_sql_log'] = [];
$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\TaskDependencyService();
$threw = null;
try {
    $service->add(['task_id' => 'TSK-0001', 'predecessor_id' => 'TSK-0002']);
} catch (\InvalidArgumentException $e) {
    $threw = $e->getMessage();
}
$RESULT = ['threw' => $threw, 'writes' => $GLOBALS['__fa_sql_log']];
PHP);

        $this->assertSame(0, $probe['exit'], ChildPhpRunner::diagnostic($probe['output']));
        $this->assertStringContainsString('cycle', (string) $probe['data']['threw']);
        // The guard has to READ the persisted graph to know it would close a
        // loop, so a SELECT is expected. What must not happen is a write.
        $this->assertSame(
            [],
            /**
             * @BABOK Related: FR-PM-006-003 — cycle guard
             */
            array_values(array_filter((array) $probe['data']['writes'], static function ($sql) {
                return stripos(ltrim((string) $sql), 'select') !== 0;
            })),
            'a rejected dependency must not be written'
        );
    }

    /**
     * UT-PM-006-005-008 / FR-PM-006-003 — the refusal names the cycle as a
     * CLOSED path, back to where it starts.
     *
     * "Dependency would create a cycle: TSK-0002" tells the user nothing about
     * which of their dependencies is in conflict. The path
     * `TSK-0002 -> TSK-0001 -> TSK-0002` is the whole point of running a real
     * tri-colour search rather than a boolean: the closing node is repeated so
     * the loop is readable in a UI and in an error message, the same
     * convention CpmEngine::run() uses for its `cycle` payload.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-008
     */
    public function testCycleRefusalReportsTheClosedPathBackToItsStart(): void
    {
        $probe = ChildPhpRunner::runJson(<<<'PHP'
$GLOBALS['__fa_select_result'] = [
    ['dependency_id' => 'DEP-0001', 'task_id' => 'TSK-0002', 'predecessor_id' => 'TSK-0001'],
];
$GLOBALS['__fa_sql_log'] = [];
$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\TaskDependencyService();
$threw = null;
try {
    $service->add(['task_id' => 'TSK-0001', 'predecessor_id' => 'TSK-0002']);
} catch (\InvalidArgumentException $e) {
    $threw = $e->getMessage();
}
$RESULT = ['threw' => $threw, 'writes' => $GLOBALS['__fa_sql_log']];
PHP);

        $this->assertSame(0, $probe['exit'], ChildPhpRunner::diagnostic($probe['output']));
        $this->assertStringContainsString(
            'TSK-0001 -> TSK-0002 -> TSK-0001',
            (string) $probe['data']['threw'],
            'the refusal must name the cycle as a closed traversal'
        );
        $this->assertSame(
            [],
            /**
             * @BABOK Related: FR-PM-006-003 — cycle guard
             */
            array_values(array_filter((array) $probe['data']['writes'], static function ($sql) {
                return stripos(ltrim((string) $sql), 'select') !== 0;
            })),
            'a rejected dependency must not be written'
        );
    }

    /**
     * UT-PM-006-005-003 / FR-PM-006-003 — an acyclic edge in an acyclic graph
     * is accepted, persists, and returns its new id.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-003
     */
    public function testAcyclicEdgePersistsAndReturnsAnId(): void
    {
        $probe = ChildPhpRunner::runJson(<<<'PHP'
$GLOBALS['__fa_select_result'] = [
    ['dependency_id' => 'DEP-0001', 'task_id' => 'TSK-0002', 'predecessor_id' => 'TSK-0001'],
];
$GLOBALS['__fa_sql_log'] = [];
$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\TaskDependencyService();
$id = null;
$error = null;
try {
    $id = $service->add(['task_id' => 'TSK-0003', 'predecessor_id' => 'TSK-0002']);
} catch (\Throwable $e) {
    $error = get_class($e) . ': ' . $e->getMessage();
}
$RESULT = ['id' => $id, 'error' => $error, 'writes' => $GLOBALS['__fa_sql_log']];
PHP);

        $this->assertSame(0, $probe['exit'], ChildPhpRunner::diagnostic($probe['output']));
        $this->assertNull($probe['data']['error'], 'an acyclic edge must not be refused');
        $this->assertSame('DEP-0002', $probe['data']['id']);
        $this->assertNotEmpty($probe['data']['writes'], 'the edge must be written');
    }

    /**
     * UT-PM-006-005-004 / FR-PM-006-003 — deleteAllFor removes all deps of a
     * task.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-005-004
     */
    public function testDeleteAllForRemovesEveryEdgeOfATask(): void
    {
        $GLOBALS['__fa_select_queue']  = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_sql_log']       = [];

        $repo = new \ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository();
        $repo->deleteAllFor('TSK-0002');

        $this->assertCount(1, $GLOBALS['__fa_sql_log']);
        $sql = (string) $GLOBALS['__fa_sql_log'][0];
        $this->assertStringStartsWith('DELETE FROM 0_ksf_projectmanagement_task_dependencies', $sql);
        $this->assertStringContainsString("task_id = 'TSK-0002'", $sql);
    }
}