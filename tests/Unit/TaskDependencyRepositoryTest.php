<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository;
use PHPUnit\Framework\TestCase;

/**
 * TaskDependencyRepository — DAO tests against the GLOBALS-backed fake DB.
 *
 * Same approach as TaskRepositoryTest: the repository codes against
 * DbConnectionInterface and the fake db_* from tests/stubs.php, so no second DB
 * approach is introduced.
 *
 * @BABOK Related: FR-PM-006-003, BR-PM-006
 */
class TaskDependencyRepositoryTest extends TestCase
{
    /** @var TaskDependencyRepository */
    private $repo;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue']  = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql']      = '';
        $GLOBALS['__fa_sql_log']       = [];
        $GLOBALS['__fa_next_id']       = 1;
        $this->repo = new TaskDependencyRepository();
    }

    /**
     * @param string $taskId
     * @param string $predecessorId
     * @return array<string,mixed>
     */
    private function row(string $taskId, string $predecessorId): array
    {
        return [
            'dependency_id'   => $taskId . '~' . $predecessorId,
            'task_id'         => $taskId,
            'predecessor_id'  => $predecessorId,
            'dependency_type' => 'FS',
            'lag'             => 0,
        ];
    }

    public function testFindAllForFiltersOnTaskId(): void
    {
        $GLOBALS['__fa_select_result'] = [$this->row('TSK-0002', 'TSK-0001')];

        $rows = $this->repo->findAllFor('TSK-0002');

        $this->assertCount(1, $rows);
        $this->assertSame('TSK-0001', $rows[0]['predecessor_id']);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('FROM 0_fa_pm_task_dependencies', $sql);
        $this->assertStringContainsString("task_id = 'TSK-0002'", $sql);
    }

    public function testFindAllByProjectJoinsTheTaskDictionary(): void
    {
        $GLOBALS['__fa_select_result'] = [$this->row('TSK-0002', 'TSK-0001')];

        $rows = $this->repo->findAllByProject('PRJ-0001');

        $this->assertCount(1, $rows);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('INNER JOIN 0_fa_pm_tasks', $sql);
        $this->assertStringContainsString("project_id = 'PRJ-0001'", $sql);
    }

    public function testFindAllForProjectIsAnAliasOfFindAllByProject(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [$this->row('TSK-0002', 'TSK-0001')],
            [$this->row('TSK-0003', 'TSK-0002')],
        ];

        $first  = $this->repo->findAllByProject('PRJ-0001');
        $second = $this->repo->findAllForProject('PRJ-0001');

        $this->assertSame('TSK-0002', $first[0]['task_id']);
        $this->assertSame('TSK-0003', $second[0]['task_id']);
    }

    public function testNextIdStartsAtThePaddedSequence(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->assertSame('DEP-0001', $this->repo->nextId());
    }

    public function testNextIdIncrementsFromTheLastRow(): void
    {
        $GLOBALS['__fa_select_result'] = [['dependency_id' => 'DEP-0007']];

        $this->assertSame('DEP-0008', $this->repo->nextId());
    }

    public function testInsertWritesThePrefixedTableAndReturnsTheNewId(): void
    {
        $GLOBALS['__fa_select_queue'] = [[], []];

        $id = $this->repo->insert([
            'task_id'         => 'TSK-0002',
            'predecessor_id'  => 'TSK-0001',
            'dependency_type' => 'SS',
            'lag'             => 2,
        ]);

        $this->assertSame('DEP-0001', $id);
        $this->assertNotEmpty($GLOBALS['__fa_sql_log']);
        $sql = (string) end($GLOBALS['__fa_sql_log']);
        $this->assertStringContainsString('INSERT INTO `0_fa_pm_task_dependencies`', $sql);
        $this->assertStringContainsString("'TSK-0002'", $sql);
        $this->assertStringContainsString("'TSK-0001'", $sql);
    }

    public function testDeleteScopesOnBothIds(): void
    {
        $this->repo->delete('TSK-0002', 'TSK-0001');

        $this->assertCount(1, $GLOBALS['__fa_sql_log']);
        $sql = (string) $GLOBALS['__fa_sql_log'][0];
        $this->assertStringStartsWith('DELETE FROM 0_fa_pm_task_dependencies', $sql);
        $this->assertStringContainsString("task_id = 'TSK-0002'", $sql);
        $this->assertStringContainsString("predecessor_id = 'TSK-0001'", $sql);
    }

    public function testDeleteAllForScopesOnTheTaskAlone(): void
    {
        $this->repo->deleteAllFor('TSK-0002');

        $sql = (string) $GLOBALS['__fa_sql_log'][0];
        $this->assertStringContainsString("task_id = 'TSK-0002'", $sql);
        $this->assertStringNotContainsString('predecessor_id =', $sql);
    }
}