<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use Ksfraser\Tests\ChildPhpRunner;
use PHPUnit\Framework\TestCase;

/**
 * SchedulingService — FR-PM-006-002 orchestration and persistence.
 *
 * UT-PM-006 covers CpmEngine only; this file covers the FR-PM-006-002 side of
 * the same requirement (run the engine, persist the computed field set, fire
 * the workflow hook) so the RTM's "FR-006-002 covered" claim has an executable
 * basis.
 *
 * Every scenario runs in a CHILD process. SchedulingService cannot be loaded
 * in-process at all: it spells the trait import `Ksfraser\FrontAccounting\...`
 * with a capital K, which does not match the module's PSR-4 prefix
 * (`ksfraser\FrontAccounting\`), so `use WorkflowHooksTrait;` raises a PHP
 * fatal. A fatal cannot be caught, so probing out-of-process is the only way
 * to observe the class without aborting the whole suite.
 *
 * @BABOK Related: FR-PM-006-002, BR-PM-006
 */
class SchedulingServiceTest extends TestCase
{
    /**
     * Seed the two SELECTs SchedulingService issues (tasks, then edges) into
     * the fake DB, run schedule(), and hand back everything observable.
     *
     * @param array<int,array<string,mixed>> $tasks
     * @param array<int,array<string,mixed>> $deps
     * @return array{exit:int,output:string,data:mixed}
     */
    private function schedule(array $tasks, array $deps): array
    {
        $body = <<<'PHP'
$GLOBALS['__fa_select_queue'] = [__TASKS__, __DEPS__];
$GLOBALS['__fa_select_result'] = [];
$GLOBALS['__fa_sql_log'] = [];
$GLOBALS['__fa_hooks_fired'] = [];
$GLOBALS['__fa_next_id'] = 1;

$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\SchedulingService(
    new \ksfraser\FrontAccounting\ProjectManagement\Repository\TaskRepository(),
    new \ksfraser\FrontAccounting\ProjectManagement\Repository\TaskDependencyRepository()
);
$result = $service->schedule('PRJ-0001');
$RESULT = [
    'ok'                => !empty($result['ok']),
    'project_duration'  => $result['project_duration'] ?? null,
    'tasks'             => $result['tasks'] ?? null,
    'critical'          => $result['critical'] ?? null,
    'hooks_fired'       => $GLOBALS['__fa_hooks_fired'],
    'sql_log'           => $GLOBALS['__fa_sql_log'],
];
PHP;

        $body = str_replace(['__TASKS__', '__DEPS__'], [$this->php($tasks), $this->php($deps)], $body);

        return ChildPhpRunner::runJson($body);
    }

    /**
     * Render an array as a PHP literal for the child snippet.
     *
     * @param array<int,array<string,mixed>> $rows
     * @return string
     */
    private function php(array $rows): string
    {
        return var_export($rows, true);
    }

    /**
     * Fail with the child's own diagnostic — a fatal error message is the
     * evidence, so it belongs in the failure text.
     *
     * @param array{exit:int,output:string,data:mixed} $probe
     * @return void
     */
    private function assertLoaded(array $probe): void
    {
        $this->assertSame(
            0,
            $probe['exit'],
            'SchedulingService must be loadable: ' . ChildPhpRunner::diagnostic($probe['output'])
        );
    }

    /**
     * @param string $taskId
     * @param int    $hours
     * @return array<string,mixed>
     */
    private function taskRow(string $taskId, int $hours): array
    {
        return [
            'task_id'         => $taskId,
            'name'            => $taskId,
            'estimated_hours' => $hours,
            'is_milestone'    => 0,
        ];
    }

    /**
     * @param string $taskId
     * @param string $predecessor
     * @return array<string,mixed>
     */
    private function depRow(string $taskId, string $predecessor): array
    {
        return [
            'dependency_id'   => $taskId . '~' . $predecessor,
            'task_id'         => $taskId,
            'predecessor_id'  => $predecessor,
            'dependency_type' => 'FS',
            'lag'             => 0,
        ];
    }

    /**
     * UT-PM-006-002-001 / FR-PM-006-002 — persists results via injected
     * repository (no FA calls).
     *
     * @BABOK Related: FR-PM-006-002, UT-PM-006-002-001
     */
    public function testSchedulePersistsComputedFieldsViaInjectedRepository(): void
    {
        $probe = $this->schedule(
            [$this->taskRow('TSK-0001', 64), $this->taskRow('TSK-0002', 40)],
            [$this->depRow('TSK-0002', 'TSK-0001')]
        );
        $this->assertLoaded($probe);

        $d = $probe['data'];
        $this->assertTrue($d['ok']);
        // 64h -> 8 days, 40h -> 5 days, FS chain -> B starts on day 8.
        $this->assertSame(8, $d['tasks']['TSK-0001']['ef']);
        $this->assertSame(8, $d['tasks']['TSK-0002']['es']);
        $this->assertSame(13, $d['project_duration']);

        $updates = array_values(array_filter($d['sql_log'], static function ($sql) {
            return stripos((string) $sql, 'update ') === 0;
        }));
        $this->assertCount(2, $updates, 'one UPDATE per task row');
        $this->assertStringContainsString('0_fa_pm_tasks', $updates[0]);
        $this->assertMatchesRegularExpression('/\bes\b/', $updates[0]);
    }

    /**
     * Estimated hours are the repository's unit; the engine wants days.
     *
     * @BABOK Related: FR-PM-006-002
     */
    public function testScheduleConvertsEstimatedHoursToDurationDays(): void
    {
        $probe = $this->schedule([$this->taskRow('TSK-0001', 20)], []);
        $this->assertLoaded($probe);

        $this->assertSame(3, $probe['data']['tasks']['TSK-0001']['duration'], '20h / 8h-per-day = 2.5 -> 3');
        $this->assertSame(3, $probe['data']['project_duration']);
    }

    /**
     * UT-PM-006-002-002 / FR-PM-006-002 — fires `schedule_saved` exactly once
     * on success.
     *
     * @BABOK Related: FR-PM-006-002, UT-PM-006-002-002
     */
    public function testScheduleFiresScheduleSavedExactlyOnce(): void
    {
        $probe = $this->schedule([$this->taskRow('TSK-0001', 8)], []);
        $this->assertLoaded($probe);

        $this->assertCount(
            1,
            $probe['data']['hooks_fired'],
            'FR-PM-006-002 requires a single schedule_saved hook; got: '
            . implode(', ', $probe['data']['hooks_fired'])
        );
        $this->assertSame('schedule_saved', $probe['data']['hooks_fired'][0]);
    }

    /**
     * UT-PM-006-002-003 / FR-PM-006-002 — ok=false path returns early, no hook
     * fired.
     *
     * @BABOK Related: FR-PM-006-002, UT-PM-006-002-003
     */
    public function testScheduleOnCycleReturnsEarlyWithoutFiringHooksOrWriting(): void
    {
        $probe = $this->schedule(
            [$this->taskRow('TSK-0001', 8), $this->taskRow('TSK-0002', 8)],
            [
                $this->depRow('TSK-0002', 'TSK-0001'),
                $this->depRow('TSK-0001', 'TSK-0002'),
            ]
        );
        $this->assertLoaded($probe);

        $d = $probe['data'];
        $this->assertFalse($d['ok']);
        $this->assertSame([], $d['hooks_fired'], 'no hook on the ok=false path');
        $this->assertSame(
            [],
            array_values(array_filter($d['sql_log'], static function ($sql) {
                return stripos((string) $sql, 'update ') === 0;
            })),
            'no scheduling fields written on a cycle'
        );
    }

    /**
     * FR-PM-006-002 states the contract as
     * `schedule(projectsId, startDate, tasks, dependencies)`.
     *
     * Read from the source rather than via ReflectionMethod: reflection would
     * have to autoload the class, which fatals in-process.
     *
     * @BABOK Related: FR-PM-006-002, UT-PM-006-002-004
     */
    public function testScheduleSignatureMatchesTheFunctionalRequirement(): void
    {
        $path = dirname(__DIR__, 2)
            . '/src/Ksfraser/FrontAccounting/ProjectManagement/Service/SchedulingService.php';
        $source = (string) file_get_contents($path);

        $this->assertSame(
            1,
            preg_match('/public\s+function\s+schedule\s*\(([^)]*)\)/', $source, $m),
            'SchedulingService::schedule must exist'
        );

        $params = array_values(array_filter(array_map('trim', explode(',', $m[1]))));
        $this->assertSame(
            ['$projectId', '$startDate', '$tasks', '$dependencies', '$endDate'],
            $params,
            'FR-PM-006-002 specifies schedule(projectsId, startDate, tasks, dependencies, [endDate])'
        );
    }

    /**
     * FR-PM-006-002 requires the hook payload to carry the project id, the
     * duration, the critical set and ok.
     *
     * @BABOK Related: FR-PM-006-002
     */
    public function testScheduleHookPayloadCarriesDurationCriticalSetAndOk(): void
    {
        $probe = $this->schedule(
            [$this->taskRow('TSK-0001', 64), $this->taskRow('TSK-0002', 40)],
            [$this->depRow('TSK-0002', 'TSK-0001')]
        );
        $this->assertLoaded($probe);

        $fired = $probe['data']['hooks_fired'];
        $this->assertNotEmpty($fired, 'a hook must fire');

        $payload = ChildPhpRunner::runJson(<<<'PHP'
$GLOBALS['__fa_select_queue'] = [['task_id' => 'TSK-0001', 'estimated_hours' => 64, 'is_milestone' => 0]];
$GLOBALS['__fa_select_result'] = [];
$GLOBALS['__fa_sql_log'] = [];
$GLOBALS['__fa_next_id'] = 1;
$GLOBALS['__fa_hook_handlers']['pm_schedule_schedule_saved'] = function (&$data, $opts = null) {
    $GLOBALS['__captured'] = $data;
};
$service = new \ksfraser\FrontAccounting\ProjectManagement\Service\SchedulingService();
$service->schedule('PRJ-0001');
$RESULT = $GLOBALS['__captured'] ?? null;
PHP);
        $this->assertLoaded($payload);

        $this->assertSame('PRJ-0001', $payload['data']['project_id'] ?? null);
        $this->assertSame(8, $payload['data']['duration'] ?? null);
        $this->assertIsArray($payload['data']['critical'] ?? null);
        $this->assertTrue($payload['data']['ok'] ?? null);
    }
}