<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository;
use PHPUnit\Framework\TestCase;

/**
 * TaskRepository — DAO-ported tests (DbConnectionInterface against the fake DB).
 *
 * @BABOK Related: BR-012 (Tasks)
 */
class TaskRepositoryTest extends TestCase
{
    /** @var TaskRepository */
    private $repo;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $this->repo = new TaskRepository();
    }

    public function testFindPageAppliesFilters(): void
    {
        $GLOBALS['__fa_select_result'] = [['task_id' => 'TSK-0001', 'name' => 'Write spec']];

        $rows = $this->repo->findPage(1, 10, ['project_id' => 'PRJ-0001', 'status' => 'In Progress']);

        $this->assertCount(1, $rows);
        $this->assertSame('TSK-0001', $rows[0]['task_id']);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('FROM 0_fa_pm_tasks', $sql);
        $this->assertStringContainsString('project_id = ', $sql);
        $this->assertStringContainsString('status = ', $sql);
    }

    public function testParentOptionsExcludesSelf(): void
    {
        $GLOBALS['__fa_select_result'] = [
            ['task_id' => 'TSK-0001', 'name' => 'Spec'],
            ['task_id' => 'TSK-0002', 'name' => 'Build'],
        ];

        $options = $this->repo->parentOptions('PRJ-0001', 'TSK-0001');

        $this->assertArrayHasKey('TSK-0001', $options);
        $this->assertArrayHasKey('TSK-0002', $options);
        $this->assertStringContainsString('task_id <> ', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testParentOptionsWithoutExclude(): void
    {
        $GLOBALS['__fa_select_result'] = [['task_id' => 'TSK-0001', 'name' => 'Spec']];

        $this->repo->parentOptions('PRJ-0001');

        $this->assertStringNotContainsString('task_id <> ', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testNextIdUsesTaskPrefix(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->assertSame('TSK-0001', $this->repo->nextId());
    }

    public function testNextIdIncrements(): void
    {
        $GLOBALS['__fa_select_result'] = [['task_id' => 'TSK-0007']];

        $this->assertSame('TSK-0008', $this->repo->nextId());
    }

    public function testSaveInsertsPrefixedTable(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $id = $this->repo->save([
            'project_id' => 'PRJ-0001',
            'name' => 'Write spec',
            'estimated_hours' => 8,
        ]);

        $this->assertSame('TSK-0001', $id);
        $this->assertStringContainsString('INTO `0_fa_pm_tasks`', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testUpdateScopedByTaskId(): void
    {
        $this->repo->update('TSK-0001', ['status' => 'Completed', 'progress' => 100]);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('UPDATE `0_fa_pm_tasks`', $sql);
        $this->assertStringContainsString('updated_at', $sql);
    }

public function testDeleteCascadesProgressFirst(): void
    {
        $GLOBALS['__fa_sql_log'] = [];

        $this->repo->delete('TSK-0001');

        $log = array_map('strval', $GLOBALS['__fa_sql_log']);
        $this->assertCount(2, $log);
        $this->assertStringContainsString('DELETE FROM 0_fa_pm_task_progress', $log[0]);
        $this->assertStringContainsString('DELETE FROM `0_fa_pm_tasks`', $log[1]);
    }
}