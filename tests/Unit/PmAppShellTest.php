<?php

declare(strict_types=1);

namespace Ksfraser\Tests\Unit\FAProjectManagement;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use ksfraser\FrontAccounting\ProjectManagement\App\PmAppShell;
use ksfraser\FrontAccounting\ProjectManagement\Controller\ProjectsTabController;
use PHPUnit\Framework\TestCase;

/**
 * PmAppShell — app-shell SRP: core tab registry, register-with-me hook name,
 * pre-session security resolution and default view fallback.
 *
 * @BABOK Related: FR-PM-010-001, BR-PM-010 — the app shell and tab registration.
 *   Replaces the previous `@BABOK BR-012`, which matches no ProjectDcs document.
 *   Architectural reference retained below: APP_TAB_ARCHITECTURE.md §6/§7.
 */
class PmAppShellTest extends TestCase
{
    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testShellIsAnAppShell(): void
    {
        $this->assertInstanceOf(AbstractAppShell::class, new PmAppShell());
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testRegisterHookNameDerivesFromAppId(): void
    {
        $shell = new PmAppShell();

        $this->assertSame('pm_register_tabs', $shell->getRegisterHook());
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testDefaultViewIsDashboard(): void
    {
        $shell = new PmAppShell();

        $this->assertSame('dashboard', $shell->getDefaultView());
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testCoreTabsAreRegistered(): void
    {
        $shell = new PmAppShell();
        $keys = [];
        foreach ($shell->getTabs() as $tab) {
            $keys[] = $tab->getKey();
        }

        $this->assertSame(
            ['dashboard', 'projects', 'tasks', 'team', 'reports', 'project_types'],
            $keys
        );
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testCoreTabsAreControllerBacked(): void
    {
        $shell = new PmAppShell();

        $this->assertTrue($shell->getTab('projects') instanceof TabRegistration);
        $this->assertSame(ProjectsTabController::class, $shell->getTab('projects')->getControllerClass());
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testGetSecurityResolvesPreSessionArea(): void
    {
        $shell = new PmAppShell();

        $this->assertSame('SA_ksf_FA_ProjectManagementVIEW', $shell->getSecurity('projects'));
        $this->assertSame('', $shell->getSecurity('bogus'));
        $this->assertSame('SA_FALLBACK', $shell->getSecurity('bogus', 'SA_FALLBACK'));
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testResolveViewFallsBackToDefault(): void
    {
        $shell = new PmAppShell();

        $this->assertSame('projects', $shell->resolveView('projects')->getKey());
        $this->assertSame('dashboard', $shell->resolveView('bogus')->getKey());
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testTabsAreSortedByPriority(): void
    {
        $shell = new PmAppShell();
        $tabs = $shell->getTabs();
        $count = count($tabs);

        for ($i = 0; $i < $count - 1; $i++) {
            $this->assertLessThanOrEqual($tabs[$i + 1]->getPriority(), $tabs[$i]->getPriority());
        }
    }

    /**
     * @BABOK Related: FR-PM-010-001 — the app shell / tab surface
     */
    public function testDispatchDashboardDoesNotThrowOutsideFa(): void
    {
        $shell = new PmAppShell();

        // Renders nothing (FA UI helpers absent) but must not fatal.
        $shell->dispatch('dashboard');
        $this->addToAssertionCount(1);
    }
}