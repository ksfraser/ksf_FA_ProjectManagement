<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Dictionary\Schema;
use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectRepository;
use PHPUnit\Framework\TestCase;

/**
 * ProjectRepository — DAO-ported tests (DbConnectionInterface + TableDefinition
 * + QueryBuilder against the GLOBALS-backed fake DB).
 *
 * @BABOK Related: BR-012 (Projects)
 */
class ProjectRepositoryTest extends TestCase
{
    /** @var ProjectRepository */
    private $repo;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $this->repo = new ProjectRepository();
    }

    public function testFindPagePrefixedAndOrdered(): void
    {
        $GLOBALS['__fa_select_result'] = [[
            'project_id' => 'PRJ-0001',
            'name' => 'Website Rebuild',
            'start_date' => '2026-09-01',
        ]];

        $rows = $this->repo->findPage(1, 10, ['status' => 'Active']);

        $this->assertCount(1, $rows);
        $this->assertSame('PRJ-0001', $rows[0]['project_id']);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('FROM 0_fa_pm_projects', $sql);
        $this->assertStringContainsString('status = ', $sql);
        $this->assertStringContainsString('ORDER BY start_date DESC', $sql);
        $this->assertStringContainsString('LIMIT', $sql);
    }

    public function testCountAppliesFilters(): void
    {
        $GLOBALS['__fa_select_result'] = [['COUNT(*)' => '3']];

        $count = $this->repo->countAll(['customer_id' => 77]);

        $this->assertSame(3, $count);
        $this->assertStringContainsString('customer_id = ', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testCountReturnsZeroWhenNoRow(): void
    {
        $GLOBALS['__fa_select_result'] = [['COUNT(*)' => '0']];

        $this->assertSame(0, $this->repo->countAll());
    }

    public function testFindById(): void
    {
        $GLOBALS['__fa_select_result'] = [['project_id' => 'PRJ-0001', 'name' => 'Website Rebuild']];

        $row = $this->repo->findById('PRJ-0001');

        $this->assertSame('PRJ-0001', $row['project_id']);
        $this->assertStringContainsString('project_id = ', (string) $GLOBALS['__fa_last_sql']);
    }

    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->assertNull($this->repo->findById('PRJ-9999'));
    }

    public function testNextIdStartsAtPaddedSequence(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->assertSame('PRJ-0001', $this->repo->nextId());
    }

    public function testNextIdIncrementsFromLast(): void
    {
        $GLOBALS['__fa_select_result'] = [['project_id' => 'PRJ-0042']];

        $this->assertSame('PRJ-0043', $this->repo->nextId());
    }

    public function testSaveInsertsDictionaryDrivenAndReturnsId(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $id = $this->repo->save([
            'name' => 'Website Rebuild',
            'start_date' => '2026-09-01',
            'budget' => 50000,
        ]);

        $this->assertSame('PRJ-0001', $id);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('INTO `0_fa_pm_projects`', $sql);
        $this->assertStringContainsString('created_at', $sql);
    }

    public function testSaveNormalizesBlankCustomerTypeToNull(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->repo->save([
            'name' => 'Website Rebuild',
            'start_date' => '2026-09-01',
            'customer_id' => '',
            'end_date' => '',
        ]);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringNotContainsString("', 0_fa_pm_projects", $sql);
        $this->assertStringContainsString('INTO `0_fa_pm_projects`', $sql);
    }

    public function testUpdateUsesUpdateSqlWithId(): void
    {
        $this->repo->update('PRJ-0001', ['status' => 'Completed', 'budget' => 90000]);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('UPDATE `0_fa_pm_projects`', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('project_id', $sql);
    }

    public function testDeleteUsesDictionaryDeleteSql(): void
    {
        $this->repo->delete('PRJ-0001');

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('DELETE FROM `0_fa_pm_projects`', $sql);
        $this->assertStringContainsString('project_id', $sql);
    }

    public function testCreateSqlsAreGeneratedForAllTables(): void
    {
        $sqls = Schema::createSqls();

        $this->assertCount(9, $sqls);
        $this->assertStringContainsString('fa_pm_projects', $sqls[Schema::T_PROJECTS]);
        $this->assertStringContainsString('CREATE TABLE', $sqls[Schema::T_REVENUE]);
    }
}