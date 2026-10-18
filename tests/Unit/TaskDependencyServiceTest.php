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
     * @BABOK Related: FR-PM-006-003
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
     * The cycle guard delegates to CpmEngine::detectCycle(TaskDependencyGraph).
     * Neither collaborator is present in the shipped tree.
     *
     * @BABOK Related: FR-PM-006-003
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
     * `$this->deps->all()`, a method the repository does not define.
     *
     * @BABOK Related: FR-PM-006-003
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
     * UT-PM-006-003-001 / FR-PM-006-003 — self-dependency rejected (no write).
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-003-001
     */
    public function testSelfDependencyIsRejectedWithoutAWrite(): void
    {
        $probe = ChildPhpRunner::runJson(<<<'PHP'
$GLOBALS['__fa_select_result'] = [];
$GLOBALS['__fa_sql_log'] = [];
$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\TaskDependencyService();
$threw = null;
try {
    $service->createDependency(['task_id' => 'TSK-0001', 'predecessor_id' => 'TSK-0001']);
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
     * UT-PM-006-003-002 / FR-PM-006-003 — a back-edge that closes a cycle is
     * rejected (no write).
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-003-002
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
    $service->createDependency(['task_id' => 'TSK-0001', 'predecessor_id' => 'TSK-0002']);
} catch (\InvalidArgumentException $e) {
    $threw = $e->getMessage();
}
$RESULT = ['threw' => $threw, 'writes' => $GLOBALS['__fa_sql_log']];
PHP);

        $this->assertSame(0, $probe['exit'], ChildPhpRunner::diagnostic($probe['output']));
        $this->assertStringContainsString('cycle', (string) $probe['data']['threw']);
        $this->assertSame([], $probe['data']['writes'], 'nothing may be written');
    }

    /**
     * UT-PM-006-003-003 / FR-PM-006-003 — an acyclic edge persists and returns
     * its new id.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-003-003
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
    $id = $service->createDependency(['task_id' => 'TSK-0003', 'predecessor_id' => 'TSK-0002']);
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
     * UT-PM-006-003-004 / FR-PM-006-003 — deleteAllFor removes all deps of a
     * task.
     *
     * @BABOK Related: FR-PM-006-003, UT-PM-006-003-004
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
        $this->assertStringStartsWith('DELETE FROM 0_fa_pm_task_dependencies', $sql);
        $this->assertStringContainsString("task_id = 'TSK-0002'", $sql);
    }
}