<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use PHPUnit\Framework\TestCase;

/**
 * Capability contract and order_imported listener on
 * hooks_ksf_FA_ProjectManagement.
 *
 * @BABOK Related: N/A — the inter-module capability contract is not specified in this
 *   repo. Replaces the previous `@BABOK FR-PM-011`, which matches no ProjectDcs
 *   document in either repo. The cross-module hooks exercised below ARE specified:
 *   BR-007 / FR-PM-007-001 / FR-PM-007-002, and are cited on the individual tests.
 */
class HooksTest extends TestCase
{
    /** @var \hooks_ksf_FA_ProjectManagement */
    private $hooks;

    /**
     * @BABOK Related: N/A — hook-surface test; see the class docblock
     */
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/hooks.php';
        $this->hooks = new \hooks_ksf_FA_ProjectManagement();
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_sql_log'] = [];
        $GLOBALS['__fa_next_id'] = 1;
    }

    /**
     * @BABOK Related: N/A — hook-surface test; see the class docblock
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['__fa_select_queue'], $GLOBALS['__fa_select_result'], $GLOBALS['__fa_last_sql'], $GLOBALS['__fa_sql_log']);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testGetModuleConstants(): void
    {
        $data = [];
        $result = $this->hooks->getModuleConstants($data);

        $this->assertArrayHasKey('KSF_PM_MODULE_NAME', $result);
        $this->assertSame('ksf_FA_ProjectManagement', $result['KSF_PM_MODULE_NAME']);
        $this->assertArrayHasKey('KSF_PM_CAPABILITIES', $result);
        $this->assertArrayHasKey('constants', $data);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
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

    /**
     * @BABOK Related: N/A — sales-order linkage has no requirement in this repo (was FR-PM-009)
     */
    public function testHasCapabilitySalesOrderLink(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'sales_order_link']);

        $this->assertTrue($result);
        $this->assertTrue($data['has_capability']);
        $this->assertSame('sales_order_link', $data['capability_checked']);
    }

    /**
     * @BABOK Related: N/A — revenue recognition has no requirement in this repo (was FR-PM-010)
     */
    public function testHasCapabilityRevenue(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'revenue']);

        $this->assertTrue($result);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testHasCapabilityUnknown(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data, ['capability' => 'nonexistent']);

        $this->assertFalse($result);
        $this->assertFalse($data['has_capability']);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testHasCapabilityNoCapabilityReturnsFalse(): void
    {
        $data = [];
        $result = $this->hooks->hasCapability($data);

        $this->assertFalse($result);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testRespondToCapabilityRequestCapabilities(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'capabilities']);

        $this->assertArrayHasKey('sales_order_link', $result);
        $this->assertSame('capabilities', $data['request']);
        $this->assertSame('ksf_FA_ProjectManagement', $data['module']);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testRespondToCapabilityRequestConstants(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'constants']);

        $this->assertArrayHasKey('KSF_PM_MODULE_NAME', $result);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testRespondToCapabilityRequestHasCapability(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'has:revenue']);

        $this->assertTrue($result);
    }

    /**
     * @BABOK Related: N/A — the capability contract is unspecified in this repo (was FR-PM-011)
     */
    public function testRespondToCapabilityRequestUnknownReturnsNull(): void
    {
        $data = [];
        $result = $this->hooks->respondToCapabilityRequest($data, ['request' => 'unknown']);

        $this->assertNull($result);
        $this->assertArrayHasKey('error', $data);
    }

    /**
     * @BABOK Related: N/A — sales-order linkage has no requirement in this repo (was FR-PM-009)
     */
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

    /**
     * @BABOK Related: N/A — sales-order linkage has no requirement in this repo (was FR-PM-009)
     */
    public function testOrderImportedListenerWithoutCustomerCreatesNone(): void
    {
        $data = ['fa_order_no' => 42];

        $this->hooks->order_imported($data);

        $this->assertArrayHasKey('project_links_created', $data);
        $this->assertSame(0, $data['project_links_created']);
    }

    /**
     * ksf_event_classify_attendees appends project team members (by
     * reference) to $data['classification']['member'].
     *
     * @BABOK Related: FR-PM-007-001
     */
    public function testKsfEventClassifyAppendsProjectsMembersToPayload(): void
    {
        $this->seedSelectQueue([
            [['employee_id' => 'kevin'], ['employee_id' => 'alice']],
            [['user_id' => 'kevin']],
            [],
        ]);

        $data = [
            'dto' => [
                'project_id' => 'PRJ-0001',
                'attendee_emails' => ['u1@x.test', 'u2@x.test'],
            ],
            'classification' => ['member' => [], 'external' => []],
        ];

        $this->hooks->ksf_event_classify_attendees($data);

        $this->assertSame(['u1@x.test'], $data['classification']['member']);
    }

    /**
     * The responder appends only; other responders' member tags survive.
     *
     * @BABOK Related: FR-PM-007-001
     */
    public function testKsfEventClassifyPreservesExistingMemberTags(): void
    {
        $this->seedSelectQueue([
            [['employee_id' => 'kevin']],
            [['user_id' => 'kevin']],
        ]);

        $data = [
            'dto' => [
                'project_id' => 'PRJ-0001',
                'attendee_emails' => ['u1@x.test'],
            ],
            'classification' => ['member' => ['existing@x.test'], 'external' => []],
        ];

        $this->hooks->ksf_event_classify_attendees($data);

        $this->assertSame(['existing@x.test', 'u1@x.test'], $data['classification']['member']);
    }

    /**
     * DTO with neither project nor task -> classification untouched.
     *
     * @BABOK Related: FR-PM-007-001 (AZZ)
     */
    public function testKsfEventClassifyNoProjectNoTaskAppendsNothing(): void
    {
        $data = [
            'dto' => ['event_id' => 7, 'attendee_emails' => ['u1@x.test']],
            'classification' => ['member' => ['existing@x.test'], 'external' => []],
        ];

        $this->hooks->ksf_event_classify_attendees($data);

        $this->assertSame(['existing@x.test'], $data['classification']['member']);
        $this->assertSame([], (array) $GLOBALS['__fa_sql_log']);
    }

    /**
     * ksf_event_closed seals the linked task's time window (INSERT snapshot).
     *
     * @BABOK Related: FR-PM-007-002
     */
    public function testKsfEventClosedSealsTaskWindow(): void
    {
        $this->seedSelectQueue([
            [['task_id' => 'TSK-0001', 'project_id' => 'PRJ-0001']],
            [],
        ]);

        $dto = [
            'task_id' => 'TSK-0001',
            'started_at' => '2026-09-14 09:00:00',
            'closed_at' => '2026-09-14 13:00:00',
        ];

        $this->hooks->ksf_event_closed($dto);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('INSERT INTO 0_ksf_projectmanagement_task_progress', $sql);
        $this->assertStringContainsString("'Closed'", $sql);
    }

    /**
     * Unknown task -> event_closed subscriber is a silent no-op.
     *
     * @BABOK Related: FR-PM-007-002 (AZZ)
     */
    public function testKsfEventClosedUnknownTaskNoOp(): void
    {
        $this->seedSelectQueue([[]]);

        $dto = ['task_id' => 'TSK-9999'];
        $this->hooks->ksf_event_closed($dto);

        foreach ((array) $GLOBALS['__fa_sql_log'] as $sql) {
            $prefix = strtolower(substr(ltrim((string) $sql), 0, 6));
            $this->assertNotContains($prefix, ['insert', 'update', 'delete', 'replac']);
        }
    }

    /**
     * @BABOK Related: N/A — hook-surface test; see the class docblock
     */
    private function seedSelectQueue(array $queue): void
    {
        $GLOBALS['__fa_select_queue'] = $queue;
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_sql_log'] = [];
    }
}
