<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\ProjectManagement\Service\EventCloseTaskService;
use PHPUnit\Framework\TestCase;

/**
 * EventCloseTaskService — FR-PM-007-002 task time-window close subscriber.
 *
 * @BABOK Related: BR-007, FR-PM-007-002, FR-CAL-007-002
 */
class EventCloseTaskServiceTest extends TestCase
{
    /** @var EventCloseTaskService */
    private $service;

    protected function setUp(): void
    {
        $GLOBALS['__fa_select_queue'] = [];
        $GLOBALS['__fa_select_result'] = [];
        $GLOBALS['__fa_last_sql'] = '';
        $GLOBALS['__fa_sql_log'] = [];
        $GLOBALS['__fa_next_id'] = 1;
        $this->service = new EventCloseTaskService();
    }

    /**
     * BON: window correctly equals closed_at - started_at recorded hours
     * (09:00 -> 13:00 = 4.0h), snapshotted onto the progress row (INSERT).
     */
    public function testTaskCloseSnapshotsWorkedWindow(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['task_id' => 'TSK-0001', 'project_id' => 'PRJ-0001']],
            [],
        ];

        $result = $this->service->onEventClosed([
            'event_id' => 7,
            'task_id' => 'TSK-0001',
            'started_at' => '2026-09-14 09:00:00',
            'closed_at' => '2026-09-14 13:00:00',
        ]);

        $this->assertTrue($result['closed']);
        $this->assertSame(4.0, $result['recorded_hours']);
        $this->assertFalse($result['already_closed']);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('INSERT INTO 0_fa_pm_task_progress', $sql);
        $this->assertStringContainsString("VALUES ('TSK-0001', 'work_based', 4, 0, 0, 'Closed'", $sql);
    }

    /**
     * ARI: a repeat broadcast for the same, already-closed task is a NO-OP —
     * nothing re-written, window stays closed.
     */
    public function testAlreadyClosedTaskIsNoOp(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['task_id' => 'TSK-0001', 'project_id' => 'PRJ-0001']],
            [['progress_id' => 1, 'task_id' => 'TSK-0001', 'status' => 'Closed']],
        ];
        $GLOBALS['__fa_sql_log'] = [];

        $result = $this->service->onEventClosed([
            'task_id' => 'TSK-0001',
            'started_at' => '2026-09-14 09:00:00',
            'closed_at' => '2026-09-14 13:00:00',
        ]);

        $this->assertTrue($result['closed']);
        $this->assertTrue($result['already_closed']);
        $this->assertNoWriteStatements($GLOBALS['__fa_sql_log']);
    }

    /**
     * An existing non-closed progress row is marked closed with the window.
     */
    public function testExistingTaskWindowIsSealed(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['task_id' => 'TSK-0001', 'project_id' => 'PRJ-0001']],
            [['progress_id' => 1, 'task_id' => 'TSK-0001', 'status' => 'In Progress']],
        ];

        $result = $this->service->onEventClosed([
            'task_id' => 'TSK-0001',
            'started_at' => '2026-09-14 09:00:00',
            'closed_at' => '2026-09-14 11:30:00',
        ]);

        $this->assertTrue($result['closed']);
        $this->assertSame(2.5, $result['recorded_hours']);
        $this->assertFalse($result['already_closed']);

        $sql = (string) $GLOBALS['__fa_last_sql'];
        $this->assertStringContainsString('UPDATE 0_fa_pm_task_progress', $sql);
        $this->assertStringContainsString('work_hours = 2.5', $sql);
        $this->assertStringContainsString("status = 'Closed'", $sql);
    }

    /**
     * AZZ: DTO task_id unknown to this module -> no-op, no error.
     */
    public function testUnknownTaskNoOp(): void
    {
        $GLOBALS['__fa_select_queue'] = [[]];

        $result = $this->service->onEventClosed(['task_id' => 'TSK-9999']);

        $this->assertFalse($result['closed']);
        $this->assertSame('unknown_task', $result['reason']);
        $this->assertNoWriteStatements($GLOBALS['__fa_sql_log']);
    }

    /**
     * No task_id -> no-op (subscriber must not guess the task).
     */
    public function testNoTaskIdNoOp(): void
    {
        $result = $this->service->onEventClosed(['event_id' => 7]);

        $this->assertFalse($result['closed']);
        $this->assertSame('no_task_id', $result['reason']);
        $this->assertSame([], (array) $GLOBALS['__fa_sql_log']);
    }

    private function assertNoWriteStatements(array $log): void
    {
        foreach ($log as $sql) {
            $prefix = strtolower(substr(ltrim((string) $sql), 0, 6));
            $this->assertNotContains($prefix, ['insert', 'update', 'delete', 'replac']);
        }
    }

    /**
     * Object-form DTO (toArray) is accepted without a Calendar class dependency.
     */
    public function testObjectDtoIsAccepted(): void
    {
        $GLOBALS['__fa_select_queue'] = [
            [['task_id' => 'TSK-0001', 'project_id' => 'PRJ-0001']],
            [],
        ];

        $dto = new class([]) {
            public $payload;
            public function __construct(array $payload)
            {
                $this->payload = $payload;
            }
            public function toArray(): array
            {
                return $this->payload;
            }
        };
        $dto->payload = [
            'task_id' => 'TSK-0001',
            'started_at' => '2026-09-14 09:00:00',
            'closed_at' => '2026-09-14 09:30:00',
        ];

        $result = $this->service->onEventClosed($dto);

        $this->assertTrue($result['closed']);
        $this->assertSame(0.5, $result['recorded_hours']);
        $this->assertStringContainsString('INSERT INTO 0_fa_pm_task_progress', (string) $GLOBALS['__fa_last_sql']);
    }
}