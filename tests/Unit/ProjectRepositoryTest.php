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
 * @BABOK Related: N/A — no requirement in this repo for project CRUD; that set
 *   (FR-PM-001) lives in ksf_ProjectManagement/ProjectDcs. Replaces the previous
 *   `@BABOK BR-012`, which matches no ProjectDcs document.
 */
class ProjectRepositoryTest extends TestCase
{
    /** @var ProjectRepository */
    private $repo;

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $this->repo = new ProjectRepository();
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
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

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testCountAppliesFilters(): void
    {
        $GLOBALS['__fa_select_result'] = [['COUNT(*)' => '3']];

        $count = $this->repo->countAll(['customer_id' => 77]);

        $this->assertSame(3, $count);
        $this->assertStringContainsString('customer_id = ', (string) $GLOBALS['__fa_last_sql']);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testCountReturnsZeroWhenNoRow(): void
    {
        $GLOBALS['__fa_select_result'] = [['COUNT(*)' => '0']];

        $this->assertSame(0, $this->repo->countAll());
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testFindById(): void
    {
        $GLOBALS['__fa_select_result'] = [['project_id' => 'PRJ-0001', 'name' => 'Website Rebuild']];

        $row = $this->repo->findById('PRJ-0001');

        $this->assertSame('PRJ-0001', $row['project_id']);
        $this->assertStringContainsString('project_id = ', (string) $GLOBALS['__fa_last_sql']);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testFindByIdReturnsNullWhenMissing(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->assertNull($this->repo->findById('PRJ-9999'));
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testNextIdStartsAtPaddedSequence(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->assertSame('PRJ-0001', $this->repo->nextId());
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testNextIdIncrementsFromLast(): void
    {
        $GLOBALS['__fa_select_result'] = [['project_id' => 'PRJ-0042']];

        $this->assertSame('PRJ-0043', $this->repo->nextId());
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
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

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
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

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testUpdateUsesUpdateSqlWithId(): void
    {
        $this->repo->update('PRJ-0001', ['status' => 'Completed', 'budget' => 90000]);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('UPDATE `0_fa_pm_projects`', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('project_id', $sql);
    }

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for project CRUD (was BR-012)
     */
    public function testDeleteUsesDictionaryDeleteSql(): void
    {
        $this->repo->delete('PRJ-0001');

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('DELETE FROM `0_fa_pm_projects`', $sql);
        $this->assertStringContainsString('project_id', $sql);
    }

    /**
     * @BABOK Related: N/A — dictionary DDL generation; no requirement in this repo (was BR-012)
     */
    public function testCreateSqlsAreGeneratedForAllTables(): void
    {
        $sqls = Schema::createSqls();

        // 10 tables: projects, tasks, task_dependencies, assignments,
        // project_types, activity_log, task_progress, project_sales_orders,
        // project_revenue, files. task_dependencies was previously missing
        // from both createSqls() and definitionFor()'s map — see
        // ksf_ProjectManagement ProjectDcs/003-Scheduling/CONSOLIDATION.md.
        $this->assertCount(10, $sqls);
        $this->assertStringContainsString('fa_pm_projects', $sqls[Schema::T_PROJECTS]);
        $this->assertStringContainsString('fa_pm_task_dependencies', $sqls[Schema::T_TASK_DEPENDENCIES]);
        $this->assertStringContainsString('CREATE TABLE', $sqls[Schema::T_REVENUE]);
    }
}