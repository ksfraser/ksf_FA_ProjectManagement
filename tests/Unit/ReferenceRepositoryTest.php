<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Repository\ReferenceRepository;
use PHPUnit\Framework\TestCase;

/**
 * ReferenceRepository — the three FA-wide DDL sources the PM tabs render.
 *
 * The class had NO coverage, which is how it came to still be emitting
 * hand-written SQL strings after every other DAO in the module was moved onto
 * the shared QueryBuilder. These tests pin the emitted SQL so the next pass
 * over it has to be a deliberate one.
 *
 * @BABOK Related: N/A — no requirement in this repo for the reference-option
 *   queries. Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs
 *   document.
 */
class ReferenceRepositoryTest extends TestCase
{
    /** @var ReferenceRepository */
    private $repo;

    /**
     * @BABOK Related: N/A — no requirement defined in this repo for the reference-option queries (was BR-012)
     */
    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_sql_log'] = [];
        $this->repo = new ReferenceRepository();
    }

    /**
     * Customers come from FA's own debtors_master, which the adapter prefixes
     * exactly like a PM table.
      *
 * @BABOK Related: N/A — no requirement defined in this repo for the reference-option queries (was BR-012)
 */
    public function testCustomerOptionsQuery(): void
    {
        $GLOBALS['__fa_select_result'] = [
            ['debtor_no' => 'C-1', 'name' => 'Acme'],
            ['debtor_no' => 'C-2', 'name' => 'Globex'],
        ];

        $options = $this->repo->customerOptions();

        $this->assertSame(['C-1' => 'Acme', 'C-2' => 'Globex'], $options);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('FROM 0_debtors_master', $sql);
        $this->assertStringContainsString('ORDER BY name', $sql);
    }

    /**
     * Users come from FA's own users table.
      *
 * @BABOK Related: N/A — no requirement defined in this repo for the reference-option queries (was BR-012)
 */
    public function testUserOptionsQuery(): void
    {
        $GLOBALS['__fa_select_result'] = [
            ['user_id' => 'kevin', 'real_name' => 'Kevin Fraser'],
            ['user_id' => 'nobody', 'real_name' => ''],
        ];

        $options = $this->repo->userOptions();

        // A blank real_name falls back to the user_id so the DDL is never empty.
        $this->assertSame(['kevin' => 'Kevin Fraser', 'nobody' => 'nobody'], $options);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('FROM 0_users', $sql);
    }

    /**
     * Projects come from the PM dictionary table, and completed projects are
     * excluded from task/assignment pickers.
      *
 * @BABOK Related: N/A — no requirement defined in this repo for the reference-option queries (was BR-012)
 */
    public function testProjectOptionsQuery(): void
    {
        $GLOBALS['__fa_select_result'] = [
            ['project_id' => 'PRJ-0001', 'name' => 'Website Rebuild'],
        ];

        $options = $this->repo->projectOptions();

        $this->assertSame(['PRJ-0001' => 'PRJ-0001 - Website Rebuild'], $options);
        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('FROM 0_ksf_projectmanagement_projects', $sql);
        $this->assertStringContainsString("status <> 'Completed'", $sql);
        $this->assertStringContainsString('ORDER BY start_date DESC', $sql);
    }

    /**
     * Project types are delegated to ProjectTypeRepository rather than
     * re-queried here.
      *
 * @BABOK Related: BR-006 — delegates to the cached project-type layer
 */
    public function testProjectTypeOptionsDelegatesToProjectTypeRepository(): void
    {
        $GLOBALS['__fa_select_result'] = [
            ['id' => 1, 'name' => 'Client'],
        ];

        $options = $this->repo->projectTypeOptions();

        $this->assertSame(['1' => 'Client'], $options);
        $this->assertStringContainsString('FROM 0_ksf_projectmanagement_project_types', (string) $GLOBALS['__fa_last_sql']);
    }

    /**
     * Every method is a pure read: nothing here may write.
      *
 * @BABOK Related: N/A — architecture guard: reference queries must stay read-only
 */
    public function testNoMethodWrites(): void
    {
        $GLOBALS['__fa_select_result'] = [];
        $this->repo->customerOptions();
        $this->repo->userOptions();
        $this->repo->projectOptions();
        $this->repo->projectTypeOptions();

        foreach ($GLOBALS['__fa_sql_log'] as $sql) {
            $prefix = strtolower(substr(ltrim((string) $sql), 0, 6));
            $this->assertStringStartsWith('select', $prefix);
        }
    }
}