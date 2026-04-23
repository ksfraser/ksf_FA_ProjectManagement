<?php
/**
 * FA_PM Module for FrontAccounting
 *
 * Project Management module wrapper integrating ksf-project-management package
 *
 * @package FA_PM
 * @version 1.0.0
 * @author KSFII Development Team
 * @license GPL-3.0
 */

// Module metadata
$module_name = 'FA_PM';
$module_version = '1.0.0';
$module_description = 'Project Management for FrontAccounting';
$module_author = 'KSFII Development Team';
$module_category = 'Project';
$module_min_required_version = '2.4.0';
$module_package = 'ksfraser/ksf-project-management';

// Permission constants
define('PM_VIEW_PROJECT', 'PM_VIEW_PROJECT');
define('PM_MANAGE_PROJECT', 'PM_MANAGE_PROJECT');
define('PM_VIEW_TASKS', 'PM_VIEW_TASKS');
define('PM_MANAGE_TASKS', 'PM_MANAGE_TASKS');
define('PM_VIEW_TEAM', 'PM_VIEW_TEAM');
define('PM_MANAGE_TEAM', 'PM_MANAGE_TEAM');
define('PM_VIEW_REPORTS', 'PM_VIEW_REPORTS');
define('PM_ADMIN', 'PM_ADMIN');

/**
 * Initialize module
 */
function fa_pm_module_init(): void
{
    global $fa_pm_module;

    if (!isset($fa_pm_module)) {
        $fa_pm_module = new FA_PM_Module();
    }
}

/**
 * Main module class
 */
class FA_PM_Module
{
    private Ksfraser\ProjectManagement\ProjectService $projectService;

    public function __construct()
    {
        $this->init_hooks();
        $this->init_services();
    }

    private function init_hooks(): void
    {
        add_action('fa_init', [$this, 'on_fa_init']);
        add_action('customer_deleted', [$this, 'on_customer_deleted']);
    }

    private function init_services(): void
    {
        $container = fa_pm_get_container();
        $this->projectService = $container->get(Ksfraser\ProjectManagement\ProjectService::class);
    }

    public function on_fa_init(): void
    {
        add_action('project_extra_fields', [$this, 'display_project_extra_fields']);
    }

    public function on_customer_deleted(string $customerId): void
    {
        $this->log_activity('project', $customerId, 'customer_deleted', 'Customer deleted - projects may need reassignment');
    }

    public function display_project_extra_fields(string $projectId): void
    {
        return;
    }

    private function log_activity(string $entityType, string $entityId, string $action, string $details = '', array $data = []): void
    {
        $userId = isset($_SESSION['wa_current_user']) ? $_SESSION['wa_current_user']->user : 'system';

        $sql = "INSERT INTO " . TB_PREF . "fa_pm_activity_log
            (entity_type, entity_id, user_id, action, details, new_values, created_at) VALUES
            (" . db_escape($entityType) . ", " . db_escape($entityId) . ",
             " . db_escape($userId) . ", " . db_escape($action) . ",
             " . db_escape($details) . ", " . db_escape(json_encode($data)) . ", NOW())";

        db_query($sql, "Could not log activity");
    }

    public function getProjectService(): \Ksfraser\ProjectManagement\ProjectService
    {
        return $this->projectService;
    }
}

/**
 * Get module info for FA module manager
 */
function fa_pm_get_module_info(): array
{
    return [
        'name' => $module_name,
        'version' => $module_version,
        'description' => $module_description,
        'author' => $module_author,
        'category' => $module_category,
        'depends' => [],
        'package' => $module_package,
    ];
}

/**
 * Install module hook
 */
function fa_pm_install(): bool
{
    require_once __DIR__ . '/hooks.php';
    return fa_pm_install();
}

/**
 * Activate module hook
 */
function fa_pm_activate(): bool
{
    require_once __DIR__ . '/hooks.php';
    return fa_pm_activate();
}

/**
 * Deactivate module hook
 */
function fa_pm_deactivate(): bool
{
    require_once __DIR__ . '/hooks.php';
    return fa_pm_deactivate();
}

/**
 * Uninstall module hook
 */
function fa_pm_uninstall(): bool
{
    require_once __DIR__ . '/hooks.php';
    return fa_pm_uninstall();
}

/**
 * Get menu items for the module
 */
function fa_pm_get_menu_items(): array
{
    return [
        [
            'title' => 'Projects',
            'heading' => true,
            'order' => 40,
        ],
        [
            'title' => 'Project Dashboard',
            'url' => '/modules/FA_PM/pages/dashboard.php',
            'access' => 'PM_VIEW_PROJECT',
            'parent' => 'Projects',
            'order' => 1,
        ],
        [
            'title' => 'All Projects',
            'url' => '/modules/FA_PM/pages/projects.php',
            'access' => 'PM_VIEW_PROJECT',
            'parent' => 'Projects',
            'order' => 2,
        ],
        [
            'title' => 'Tasks',
            'url' => '/modules/FA_PM/pages/tasks.php',
            'access' => 'PM_VIEW_TASKS',
            'parent' => 'Projects',
            'order' => 3,
        ],
        [
            'title' => 'Team',
            'url' => '/modules/FA_PM/pages/team.php',
            'access' => 'PM_VIEW_TEAM',
            'parent' => 'Projects',
            'order' => 4,
        ],
        [
            'title' => 'Reports',
            'url' => '/modules/FA_PM/pages/reports.php',
            'access' => 'PM_VIEW_REPORTS',
            'parent' => 'Projects',
            'order' => 5,
        ],
        [
            'title' => 'Settings',
            'url' => '/modules/FA_PM/pages/settings.php',
            'access' => 'PM_ADMIN',
            'parent' => 'Projects',
            'order' => 6,
        ],
    ];
}

/**
 * Get DI container for FA_PM module
 */
function fa_pm_get_container(): \Psr\Container\ContainerInterface
{
    static $container = null;

    if ($container === null) {
        $container = new \Ksfraser\ProjectManagement\FA\PMContainer();
    }

    return $container;
}