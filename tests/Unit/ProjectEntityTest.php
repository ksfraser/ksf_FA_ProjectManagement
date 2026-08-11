<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectSalesOrder;
use ksfraser\FrontAccounting\ProjectManagement\Entity\ProjectRevenue;
use PHPUnit\Framework\TestCase;

/**
 * Entity hydration, accessors, and serialization.
 *
 * @BABOK Related: FR-PM-009/010
 */
class ProjectEntityTest extends TestCase
{
    public function testProjectSalesOrderCanBeInstantiated(): void
    {
        $link = new ProjectSalesOrder(['project_id' => 'PRJ-001', 'fa_order_no' => 42]);
        $this->assertInstanceOf(ProjectSalesOrder::class, $link);
        $this->assertSame('PRJ-001', $link->getProjectId());
        $this->assertSame(42, $link->getFaOrderNo());
        $this->assertSame(10, $link->getFaTransType());
        $this->assertSame('all', $link->getSource());
        $this->assertNull($link->getSourceOrderId());
        $this->assertSame(0, $link->getId());
    }

    public function testProjectSalesOrderToArray(): void
    {
        $link = new ProjectSalesOrder([
            'id' => 7,
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'source' => 'square',
            'source_order_id' => 'PAY_1',
        ]);
        $array = $link->toArray();
        $this->assertSame(7, $array['id']);
        $this->assertSame('PRJ-001', $array['project_id']);
        $this->assertSame('square', $array['source']);
        $this->assertSame('PAY_1', $array['source_order_id']);
    }

    public function testProjectRevenueCanBeInstantiated(): void
    {
        $revenue = new ProjectRevenue([
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'order_total' => '1000.00',
            'revenue_amount' => '1000.00',
        ]);
        $this->assertInstanceOf(ProjectRevenue::class, $revenue);
        $this->assertSame('PRJ-001', $revenue->getProjectId());
        $this->assertSame(1000.0, $revenue->getOrderTotal());
        $this->assertSame(1000.0, $revenue->getRevenueAmount());
        $this->assertSame(0, $revenue->getRevenueId());
        $this->assertSame('', $revenue->getOrderDate());
    }

    public function testProjectRevenueToArray(): void
    {
        $revenue = new ProjectRevenue([
            'revenue_id' => 3,
            'project_id' => 'PRJ-001',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'source' => 'woocommerce',
            'source_order_id' => '99',
            'order_total' => '250.50',
            'revenue_amount' => '250.50',
            'order_date' => '2026-08-10',
        ]);
        $array = $revenue->toArray();
        $this->assertSame(3, $array['revenue_id']);
        $this->assertSame(250.50, $array['revenue_amount']);
        $this->assertSame('woocommerce', $array['source']);
        $this->assertSame('2026-08-10', $array['order_date']);
    }
}
