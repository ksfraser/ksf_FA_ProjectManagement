<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Service\ProjectService;
use PHPUnit\Framework\TestCase;

/**
 * ProjectService — workflow hooks are wired ONTO the DAO layer, exactly the
 * ksf-common-db NEXT-STEP from AGENTS.md: create/update/delete fire the
 * SuiteCRM-style pm_project_* lifecycle hooks through hook_invoke_all and
 * record an activity-log row.
 *
 * @BABOK Related: BR-012 (Projects), AGENTS.md §ksf_common_db
 */
class ProjectServiceTest extends TestCase
{
    /** @var ProjectService */
    private $service;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_next_id'] = 1;
        $GLOBALS['__fa_hooks_fired'] = [];
        $GLOBALS['__fa_hook_handlers'] = [];
        $GLOBALS['__fa_sql_log'] = [];
        $this->service = new ProjectService();
    }

    private function baseProject(): array
    {
        return [
            'name' => 'Website Rebuild',
            'start_date' => '2026-09-01',
            'budget' => 50000,
        ];
    }

    public function testCreateFiresLifecycleHooksInOrder(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $id = $this->service->create($this->baseProject());

        $this->assertSame('PRJ-0001', $id);
        $fired = $GLOBALS['__fa_hooks_fired'];
        $this->assertContains('pm_project_before_save', $fired);
        $this->assertContains('pm_project_new', $fired);
        $this->assertContains('pm_project_after_save', $fired);
        $this->assertSame(
            ['pm_project_before_save', 'pm_project_new', 'pm_project_after_save'],
            array_values(array_intersect($fired, ['pm_project_before_save', 'pm_project_new', 'pm_project_after_save']))
        );
    }

public function testCreateRecordsActivityLogRow(): void
    {
        $GLOBALS['__fa_select_result'] = [];

        $this->service->create($this->baseProject());

        $found = false;
        foreach ($GLOBALS['__fa_sql_log'] as $sql) {
            if (strpos((string) $sql, 'INSERT INTO `0_fa_pm_activity_log`') !== false) {
                $found = true;
                $this->assertStringContainsString('created', (string) $sql);
            }
        }
        $this->assertTrue($found, 'expected an activity-log INSERT');
    }

    public function testBeforeSaveHookCanMutateThePayload(): void
    {
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_sql_log'] = [];
        $GLOBALS['__fa_hook_handlers']['pm_project_before_save'] = function (&$data, $opts) {
            $data['data']['status'] = 'Active';
        };

        $this->service->create($this->baseProject());

        $found = false;
        foreach ($GLOBALS['__fa_sql_log'] as $sql) {
            if (strpos((string) $sql, 'INSERT INTO `0_fa_pm_projects`') !== false) {
                $found = true;
                $this->assertStringContainsString('Active', (string) $sql);
            }
        }
        $this->assertTrue($found, 'expected a projects INSERT carrying the mutated status');
    }

    public function testUpdateFiresEditedHook(): void
    {
        $this->service->update('PRJ-0001', ['status' => 'Completed']);

        $fired = $GLOBALS['__fa_hooks_fired'];
        $this->assertContains('pm_project_before_save', $fired);
        $this->assertContains('pm_project_edited', $fired);
        $this->assertContains('pm_project_after_save', $fired);
    }

public function testUpdateRecordsActivityLogRow(): void
    {
        $this->service->update('PRJ-0001', ['status' => 'Completed']);

        $found = false;
        foreach ($GLOBALS['__fa_sql_log'] as $sql) {
            if (strpos((string) $sql, 'INSERT INTO `0_fa_pm_activity_log`') !== false) {
                $found = true;
                $this->assertStringContainsString('updated', (string) $sql);
            }
        }
        $this->assertTrue($found, 'expected an activity-log INSERT');
    }

    public function testDeleteFiresBeforeAfterDelete(): void
    {
        $this->service->delete('PRJ-0001');

        $fired = $GLOBALS['__fa_hooks_fired'];
        $this->assertContains('pm_project_before_delete', $fired);
        $this->assertContains('pm_project_after_delete', $fired);
        $this->assertNotContains('pm_project_edited', $fired);
    }

    public function testStatusAndPriorityOptionsAreStatic(): void
    {
        $statuses = ProjectService::statusOptions();
        $priorities = ProjectService::priorityOptions();

        $this->assertArrayHasKey('Active', $statuses);
        $this->assertArrayHasKey('Medium', $priorities);
    }

    public function testFieldMetadataMatchesProjectsSchema(): void
    {
        $meta = ProjectService::getFieldMetadata();

        $this->assertSame('project_id', $meta['pk']);
        $this->assertArrayHasKey('project_id', $meta['fields']);
        $this->assertArrayHasKey('status', $meta['fields']);
    }
}