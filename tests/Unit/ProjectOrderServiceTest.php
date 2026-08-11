<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Service\ProjectOrderService;
use PHPUnit\Framework\TestCase;

/**
 * ProjectOrderService order_imported handling and linking.
 *
 * @BABOK Related: FR-PM-009/010
 */
class ProjectOrderServiceTest extends TestCase
{
    /** @var ProjectOrderService */
    private $service;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $this->service = new ProjectOrderService();
    }

    public function testOnOrderImportedLinksOrderAndRecordsRevenue(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['project_id' => 'PRJ-001', 'name' => 'Website Rebuild', 'status' => 'Active']],
            [],
        ];
        $GLOBALS['__fa_next_id'] = 100;

        $payload = [
            'source' => 'square',
            'source_order_id' => 'PAY_1',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'customer_id' => 77,
            'order_total' => 1000.00,
            'order_date' => '2026-08-10',
        ];

        $links = $this->service->onOrderImported($payload);

        $this->assertSame([['link_id' => 100, 'revenue_id' => 101]], $links);
        $this->assertStringContainsString('0_fa_pm_project_revenue', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testOnOrderImportedReturnsEmptyWithoutCustomer(): void
    {
        $links = $this->service->onOrderImported(['fa_order_no' => 42]);

        $this->assertSame([], $links);
    }

    public function testOnOrderImportedReturnsEmptyWithoutProjects(): void
    {
        $GLOBALS['__fa_select_queue'] = [[]];

        $links = $this->service->onOrderImported([
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'customer_id' => 77,
            'order_total' => 1000.00,
        ]);

        $this->assertSame([], $links);
    }

    public function testOnOrderImportedReturnsEmptyWhenAlreadyLinked(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['project_id' => 'PRJ-001', 'name' => 'Website Rebuild', 'status' => 'Active']],
            [['id' => 1, 'project_id' => 'PRJ-001', 'fa_order_no' => 42, 'fa_trans_type' => 10]],
        ];

        $links = $this->service->onOrderImported([
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'customer_id' => 77,
            'order_total' => 1000.00,
        ]);

        $this->assertSame([], $links);
    }

    public function testOnOrderImportedLinksToEveryProject(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [
                ['project_id' => 'PRJ-001', 'name' => 'Website Rebuild', 'status' => 'Active'],
                ['project_id' => 'PRJ-002', 'name' => 'Retainer', 'status' => 'Active'],
            ],
            [],
        ];
        $GLOBALS['__fa_next_id'] = 200;

        $links = $this->service->onOrderImported([
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'customer_id' => 77,
            'order_total' => 1000.00,
        ]);

        $this->assertCount(2, $links);
    }

    public function testLinkOrderToProjectReturnsNullWhenAlreadyLinked(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['id' => 1, 'project_id' => 'PRJ-001', 'fa_order_no' => 42, 'fa_trans_type' => 10]],
        ];

        $result = $this->service->linkOrderToProject('PRJ-001', [
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'order_total' => 1000.00,
        ]);

        $this->assertNull($result);
    }

    public function testLinkOrderToProjectRecordsFullRevenue(): void
    {
        $GLOBALS['__fa_select_queue'] = [[]];
        $GLOBALS['__fa_next_id'] = 5;

        $result = $this->service->linkOrderToProject('PRJ-001', [
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'source' => 'woocommerce',
            'order_total' => 250.50,
        ]);

        $this->assertNotNull($result);
        $this->assertSame(5, $result['link_id']);
        $this->assertSame(6, $result['revenue_id']);
        $this->assertStringContainsString('250.5', (string)$GLOBALS['__fa_last_sql']);
    }

    public function testGetActiveProjectsForCustomerDelegates(): void
    {
        $GLOBALS['__fa_select_result'] = [['project_id' => 'PRJ-001', 'name' => 'Website Rebuild', 'status' => 'Active']];

        $projects = $this->service->getActiveProjectsForCustomer(77);

        $this->assertCount(1, $projects);
        $this->assertSame('PRJ-001', $projects[0]['project_id']);
    }

    public function testGetRevenueSummaryDelegates(): void
    {
        $GLOBALS['__fa_select_result'] = [['revenue_total' => '100.00', 'order_count' => '1']];

        $summary = $this->service->getRevenueSummary('PRJ-001');

        $this->assertSame('100.00', $summary['revenue_total']);
        $this->assertSame('1', $summary['order_count']);
    }
}
