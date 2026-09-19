<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Repository\AssignmentRepository;
use PHPUnit\Framework\TestCase;

/**
 * AssignmentRepository — composite-key DAO tests against the fake DB.
 *
 * @BABOK Related: BR-012 (Team)
 */
class AssignmentRepositoryTest extends TestCase
{
    /** @var AssignmentRepository */
    private $repo;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $this->repo = new AssignmentRepository();
    }

    public function testFindPageAddsCompositeKeyColumn(): void
    {
        $GLOBALS['__fa_select_result'] = [[
            'project_id' => 'PRJ-0001',
            'employee_id' => 'kevin',
            'role' => 'Project Manager',
            'start_date' => '2026-09-01',
            'allocation_percentage' => '100.00',
        ]];

        $rows = $this->repo->findPage(1, 10, ['project_id' => 'PRJ-0001']);

        $this->assertCount(1, $rows);
        $this->assertSame('PRJ-0001~kevin', $rows[0]['assignment_key']);
        $this->assertStringContainsString('FROM 0_fa_pm_assignments', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testFindByCompositeKey(): void
    {
        $GLOBALS['__fa_select_result'] = [['project_id' => 'PRJ-0001', 'employee_id' => 'kevin']];

        $row = $this->repo->find('PRJ-0001', 'kevin');

        $this->assertSame('PRJ-0001', $row['project_id']);
        $this->assertStringContainsString('employee_id = ', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testSplitKey(): void
    {
        $this->assertSame(['PRJ-0001', 'kevin'], $this->repo->splitKey('PRJ-0001~kevin'));
    }

    public function testSaveReturnsCompositeKey(): void
    {
        $key = $this->repo->save([
            'project_id' => 'PRJ-0001',
            'employee_id' => 'kevin',
            'role' => 'Project Manager',
            'start_date' => '2026-09-01',
        ]);

        $this->assertSame('PRJ-0001~kevin', $key);
        $this->assertStringContainsString('INTO `0_fa_pm_assignments`', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testUpdateScopedByCompositeKey(): void
    {
        $this->repo->update('PRJ-0001~kevin', ['role' => 'Tech Lead']);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('UPDATE `0_fa_pm_assignments`', $sql);
        $this->assertStringContainsString('project_id', $sql);
        $this->assertStringContainsString('employee_id', $sql);
    }

    public function testDeleteBuildsDeleteFromSelectedQuery(): void
    {
        $this->repo->delete('PRJ-0001~kevin');

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('DELETE FROM 0_fa_pm_assignments', $sql);
        $this->assertStringContainsString('project_id', $sql);
        $this->assertStringContainsString('employee_id', $sql);
    }
}