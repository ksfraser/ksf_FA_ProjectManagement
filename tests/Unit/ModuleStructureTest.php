<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use PHPUnit\Framework\TestCase;

/**
 * @BABOK Related: N/A — architecture guard: the shipped page/dir/includes layout FA expects
 */
class ModuleStructureTest extends TestCase
{
    private string $moduleDir;
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testIncludesDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->moduleDir . '/includes');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testPagesDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->moduleDir . '/pages');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testPmDbIncExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/includes/pm_db.inc');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testDashboardPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/dashboard.php');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testProjectsPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/projects.php');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testTasksPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/tasks.php');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testGanttPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/gantt.php');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testProjectDcsExists(): void
    {
        $this->assertDirectoryExists($this->moduleDir . '/ProjectDcs');
    }
    
    /**
     * @BABOK Related: N/A — architecture guard; enforces a structural rule rather than a behavioural requirement
     */
    public function testDashboardContainsPhpCode(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/dashboard.php');
        $this->assertStringContainsString('page(', $content);
    }
}
