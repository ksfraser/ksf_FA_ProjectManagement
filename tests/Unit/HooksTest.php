<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use PHPUnit\Framework\TestCase;

/**
 * Capability contract and order_imported listener on
 * hooks_ksf_FA_ProjectManagement.
 *
 * @BABOK Related: FR-PM-011 - Inter-module capability contract
 */
class HooksTest extends TestCase
{
    /** @var \hooks_ksf_FA_ProjectManagement */
    private $hooks;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/hooks.php';
        $this->hooks = new \hooks_ksf_FA_ProjectManagement();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['__fa_select_queue'], $GLOBALS['__fa_select_result'], $GLOBALS['__fa_last_sql']);
    }

    public function testGetModuleConstants(): void
    {
        $data = [];
        $result = $this->hooks->getModuleConstants($data);

        $this->assertArrayHasKey('KSF_PM_MODULE_NAME', $result);
        $this->assertSame('ksf_FA_ProjectManagement', $result['KSF_PM_MODULE_NAME']);
        $this->assertArrayHasKey('KSF_PM_CAPABILITIES', $result);
        $this->assertArrayHasKey('constants', $data);
    }

    public function testGetModuleCapabilities(): void
    {
        $data = [];
        $result = $this->hooks->getModuleCapabilities($data);

        $this->assertArrayHasKey('project_crud', $result);
        $this->assertArrayHasKey('task_crud', $result);
        $this->assertArrayHasKey('sales_order_link', $result);
        $this->assertArrayHasKey('revenue', $result);
        $this->assertContains('ORDER_IMPORTED', $result['sales_order_link']['events']);
        $this->assertContains('ORDER_IMPORTED', $result['revenue']['events']);
        $this->assertArrayHasKey('capabilities', $data);
    }

    public function testHasCapabilitySalesOrderLink(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'sales_order_link']);

        $this->assertTrue($result);
        $this->assertTrue($data['has_capability']);
        $this->assertSame('sales_order_link', $data['capability_checked']);
    }

    public function testHasCapabilityRevenue(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'revenue']);

        $this->assertTrue($result);
    }

    public function testHasCapabilityUnknown(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'nonexistent']);

        $this->assertFalse($result);
        $this->assertFalse($data['has_capability']);
    }

    public function testHasCapabilityNoCapabilityReturnsFalse(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data);

        $this->assertFalse($result);
        $this->assertArrayHasKey('error', $data);
    }

    public function testRespondToCapabilityRequestCapabilities(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'capabilities']);

        $this->assertArrayHasKey('sales_order_link', $result);
        $this->assertSame('capabilities', $data['request']);
        $this->assertSame('ksf_FA_ProjectManagement', $data['module']);
    }

    public function testRespondToCapabilityRequestConstants(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'constants']);

        $this->assertArrayHasKey('KSF_PM_MODULE_NAME', $result);
    }

    public function testRespondToCapabilityRequestHasCapability(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'has:revenue']);

        $this->assertTrue($result);
    }

    public function testRespondToCapabilityRequestUnknownReturnsNull(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'unknown']);

        $this->assertNull($result);
        $this->assertArrayHasKey('error', $data);
    }

    public function testOrderImportedListenerLinksOrdersToProjects(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['project_id' => 'PRJ-001', 'name' => 'Website Rebuild', 'status' => 'Active']],
            [],
        ];
        $GLOBALS['__fa_next_id'] = 1;

        $data = [
            'source' => 'square',
            'source_order_id' => 'PAY_1',
            'fa_order_no' => 42,
            'fa_trans_type' => 10,
            'customer_id' => 77,
            'order_total' => 1000.00,
            'order_date' => '2026-08-10',
            'currency' => 'USD',
        ];

        $this->hooks->order_imported($data);

        $this->assertArrayHasKey('project_links_created', $data);
        $this->assertSame(1, $data['project_links_created']);
    }

    public function testOrderImportedListenerWithoutCustomerCreatesNone(): void
    {
        $data = ['fa_order_no' => 42];

        $this->hooks->order_imported($data);

        $this->assertArrayHasKey('project_links_created', $data);
        $this->assertSame(0, $data['project_links_created']);
    }
}
