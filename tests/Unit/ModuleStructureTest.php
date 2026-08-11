<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use PHPUnit\Framework\TestCase;

class ModuleStructureTest extends TestCase
{
    private string $moduleDir;
    
    protected function setUp(): void
    {
        $this->moduleDir = dirname(__DIR__, 2);
    }
    
    public function testIncludesDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->moduleDir . '/includes');
    }
    
    public function testPagesDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->moduleDir . '/pages');
    }
    
    public function testPmDbIncExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/includes/pm_db.inc');
    }
    
    public function testDashboardPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/dashboard.php');
    }
    
    public function testProjectsPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/projects.php');
    }
    
    public function testTasksPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/tasks.php');
    }
    
    public function testGanttPageExists(): void
    {
        $this->assertFileExists($this->moduleDir . '/pages/gantt.php');
    }
    
    public function testProjectDcsExists(): void
    {
        $this->assertDirectoryExists($this->moduleDir . '/ProjectDcs');
    }
    
    public function testDashboardContainsPhpCode(): void
    {
        $content = file_get_contents($this->moduleDir . '/pages/dashboard.php');
        $this->assertStringContainsString('page(', $content);
    }
}
