<?php
/**
 * FA_PM Module Metadata Test
 *
 * Tests for module metadata values
 */

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FA_PM_MetadataTest extends TestCase
{
    public function testModuleNameIsCorrect(): void
    {
        $this->assertEquals('FA_PM', 'FA_PM');
    }

    public function testModuleVersionIsCorrect(): void
    {
        $this->assertEquals('1.0.0', '1.0.0');
    }

    public function testModuleCategoryIsProject(): void
    {
        $this->assertEquals('Project', 'Project');
    }

    public function testPermissionConstantsHaveCorrectValues(): void
    {
        $this->assertEquals('PM_VIEW_PROJECT', PM_VIEW_PROJECT);
        $this->assertEquals('PM_MANAGE_PROJECT', PM_MANAGE_PROJECT);
        $this->assertEquals('PM_VIEW_TASKS', PM_VIEW_TASKS);
        $this->assertEquals('PM_MANAGE_TASKS', PM_MANAGE_TASKS);
        $this->assertEquals('PM_VIEW_TEAM', PM_VIEW_TEAM);
        $this->assertEquals('PM_MANAGE_TEAM', PM_MANAGE_TEAM);
        $this->assertEquals('PM_VIEW_REPORTS', PM_VIEW_REPORTS);
        $this->assertEquals('PM_ADMIN', PM_ADMIN);
    }

    public function testPermissionConstantsAreDistinct(): void
    {
        $constants = [
            PM_VIEW_PROJECT,
            PM_MANAGE_PROJECT,
            PM_VIEW_TASKS,
            PM_MANAGE_TASKS,
            PM_VIEW_TEAM,
            PM_MANAGE_TEAM,
            PM_VIEW_REPORTS,
            PM_ADMIN,
        ];
        $this->assertCount(8, array_unique($constants));
    }
}