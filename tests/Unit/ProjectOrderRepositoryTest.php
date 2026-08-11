<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Repository\ProjectOrderRepository;
use PHPUnit\Framework\TestCase;

/**
 * ProjectOrderRepository data access against a GLOBALS-backed fake DB.
 *
 * @BABOK Related: FR-PM-009/010
 */
class ProjectOrderRepositoryTest extends TestCase
{
    /** @var ProjectOrderRepository */
    private $repo;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $this->repo = new ProjectOrderRepository();
    }

    public function testLinkOrderInsertsIntoProjectSalesOrders(): void
    {
        $GLOBALS['__fa_next_id'] = 9;

        $id = $this->repo->linkOrder([
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'source' => 'square',
            'source_order_id' => 'PAY_1',
        ]);

        $this->assertSame(9, $id);
        $this->assertStringContainsString('INSERT INTO', (string)$GLOBALS['__fa_last_sql']);
        $this->assertStringContainsString('0_fa_pm_project_sales_orders', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testRecordRevenueInsertsIntoProjectRevenue(): void
    {
        $GLOBALS['__fa_next_id'] = 4;

        $id = $this->repo->recordRevenue([
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'source' => 'square',
            'order_total' => 1000,
            'revenue_amount' => 1000,
            'order_date' => '2026-08-10',
        ]);

        $this->assertSame(4, $id);
        $this->assertStringContainsString('0_fa_pm_project_revenue', (string)$GLOBALS['__fa_last_sql']);
        $this->assertStringContainsString('revenue_amount', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testFindLinksByOrderFiltersByOrder(): void
    {
        $GLOBALS['__fa_select_result'] = [[
            'id' => 1,
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
        ]];

        $links = $this->repo->findLinksByOrder(42, 10);

        $this->assertCount(1, $links);
        $this->assertStringContainsString('fa_order_no = 42', (string)$GLOBALS['__fa_last_sql']);
        $this->assertStringContainsString('fa_trans_type = 10', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testFindLinksByProjectFiltersByProject(): void
    {
        $GLOBALS['__fa_select_result'] = [
            ['id' => 1, 'project_id' => 'PRJ-001', 'fa_order_no' => 42, 'fa_trans_type' => 10],
            ['id' => 2, 'project_id' => 'PRJ-001', 'fa_order_no' => 43, 'fa_trans_type' => 10],
        ];

        $links = $this->repo->findLinksByProject('PRJ-001');

        $this->assertCount(2, $links);
        $this->assertStringContainsString('project_id', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testFindRevenueByProjectReturnsRows(): void
    {
        $GLOBALS['__fa_select_result'] = [[
            'revenue_id' => 1,
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'order_total' => '1000.00',
            'revenue_amount' => '1000.00',
        ]];

        $revenue = $this->repo->findRevenueByProject('PRJ-001');

        $this->assertCount(1, $revenue);
        $this->assertSame(1000.0, $revenue[0]->getRevenueAmount());
    }

    public function testFindProjectsForCustomerFiltersByCustomer(): void
    {
        $GLOBALS['__fa_select_result'] = [[
            'project_id' => 'PRJ-001',
            'name' => 'Website Rebuild',
            'status' => 'Active',
        ]];

        $projects = $this->repo->findProjectsForCustomer(77);

        $this->assertCount(1, $projects);
        $this->assertSame('PRJ-001', $projects[0]['project_id']);
        $this->assertStringContainsString('customer_id = 77', (string)$GLOBALS['__fa_last_sql']);
        $this->assertStringContainsString("status <> 'Cancelled'", (string)$GLOBALS['__fa_last_sql']);
    }

    public function testGetRevenueSummaryByProjectReturnsTotals(): void
    {
        $GLOBALS['__fa_select_result'] = [['revenue_total' => '2500.00', 'order_count' => '2']];

        $summary = $this->repo->getRevenueSummaryByProject('PRJ-001');

        $this->assertSame('2500.00', $summary['revenue_total']);
        $this->assertSame('2', $summary['order_count']);
        $this->assertStringContainsString('SUM(revenue_amount)', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testGetRevenueSummaryByProjectReturnsZeroDefaults(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $summary = $this->repo->getRevenueSummaryByProject('PRJ-001');

        $this->assertSame(0, $summary['revenue_total']);
        $this->assertSame(0, $summary['order_count']);
    }
}
