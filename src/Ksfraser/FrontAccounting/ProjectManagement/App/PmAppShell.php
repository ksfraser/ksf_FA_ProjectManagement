<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\App;

use ksfraser\FrontAccounting\Common\App\AbstractAppShell;
use ksfraser\FrontAccounting\Common\App\TabRegistration;
use ksfraser\FrontAccounting\ProjectManagement\Controller\DashboardTabController;
use ksfraser\FrontAccounting\ProjectManagement\Controller\ProjectsTabController;
use ksfraser\FrontAccounting\ProjectManagement\Controller\TasksTabController;
use ksfraser\FrontAccounting\ProjectManagement\Controller\TeamTabController;
use ksfraser\FrontAccounting\ProjectManagement\Controller\ProjectTypesTabController;
use ksfraser\FrontAccounting\ProjectManagement\Controller\ReportsTabController;

/**
 * PmAppShell — Project Management application shell SRP.
 *
 * Registers the PM core tabs (dashboard, projects, tasks, team, reports,
 * project_types), then fires the `pm_register_tabs` register-with-me hook on
 * boot() so other modules can register their own tabs into the PM app.
 *
 * Core tabs are registered at construction time so the host page can resolve
 * the per-view security area BEFORE session.inc. All tabs are controller-backed
 * (the app-shell SRP) and share the module's DAO layer — repositories built on
 * DbConnectionInterface + TableDefinition/QueryBuilder (the DAO porting
 * direction from AGENTS.md).
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: APP_TAB_ARCHITECTURE.md §7/§11 (app host)
 * @BABOK Related: BR-012 (Project Management)
 */
class PmAppShell extends AbstractAppShell
{
    /**
     * @param string $defaultView Fallback view key
     *
     * @since 1.0.0
     */
    public function __construct(string $defaultView = 'dashboard')
    {
        parent::__construct('pm', 'index.php', 'view', $defaultView);
        $this->registerCoreTabs();
    }

    /**
     * Core PM tabs (all controller-backed).
     *
     * @return void
     *
     * @since 1.0.0
     */
    protected function registerCoreTabs(): void
    {
        $tabs = [
            ['key' => 'dashboard',      'label' => '&Dashboard',       'controller' => DashboardTabController::class,      'security' => 'SA_ksf_FA_ProjectManagementVIEW'],
            ['key' => 'projects',       'label' => 'Projects',         'controller' => ProjectsTabController::class,       'security' => 'SA_ksf_FA_ProjectManagementVIEW'],
            ['key' => 'tasks',          'label' => 'Tasks',            'controller' => TasksTabController::class,          'security' => 'SA_ksf_FA_ProjectManagementVIEW'],
            ['key' => 'team',           'label' => 'Team',             'controller' => TeamTabController::class,           'security' => 'SA_ksf_FA_ProjectManagementVIEW'],
            ['key' => 'reports',        'label' => 'Reports',          'controller' => ReportsTabController::class,        'security' => 'SA_ksf_FA_ProjectManagementVIEW'],
            ['key' => 'project_types',  'label' => 'Project Types',    'controller' => ProjectTypesTabController::class,   'security' => 'SA_ksf_FA_ProjectManagementVIEW'],
        ];

        $priority = 0;
        foreach ($tabs as $tab) {
            $this->registerTab(new TabRegistration(
                $tab['key'],
                $tab['label'],
                $tab['security'],
                $tab['controller'],
                $priority,
                0
            ));
            $priority += 10;
        }
    }
}