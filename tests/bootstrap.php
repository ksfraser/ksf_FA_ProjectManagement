<?php
/**
 * Bootstrap for FA_PM Module Tests
 */

declare(strict_types=1);

set_include_path(implode(PATH_SEPARATOR, [
    __DIR__ . '/../vendor-src',
    get_include_path(),
]));

require_once __DIR__ . '/../vendor-src/Ksfraser/Common/ComposerDependencyManager.php';
require_once __DIR__ . '/../FA_PM_Module.php';

define('TB_PREF', 'fa_');
define('PROJECT_MANAGEMENT_TABLE_PREFIX', 'fa_pm_');
define('PM_VIEW_PROJECT', 'PM_VIEW_PROJECT');
define('PM_MANAGE_PROJECT', 'PM_MANAGE_PROJECT');
define('PM_VIEW_TASKS', 'PM_VIEW_TASKS');
define('PM_MANAGE_TASKS', 'PM_MANAGE_TASKS');
define('PM_VIEW_TEAM', 'PM_VIEW_TEAM');
define('PM_MANAGE_TEAM', 'PM_MANAGE_TEAM');
define('PM_VIEW_REPORTS', 'PM_VIEW_REPORTS');
define('PM_ADMIN', 'PM_ADMIN');