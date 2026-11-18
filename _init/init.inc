<?php
/**
 * FA_PM Module Initialization
 *
 * Called during FA startup to initialize PM module
 *
 * @package FA_PM
 * @version 1.0.0
 */

if (!defined('FA_PM_INIT_LOADED')) {
    define('FA_PM_INIT_LOADED', true);

    $path_to_root = dirname(__DIR__) . '/../..';

    if (is_readable($path_to_root . '/includes/session.inc')) {
        include_once($path_to_root . '/includes/session.inc');
    }

    if (is_readable(__DIR__ . '/PMContainer.php')) {
        include_once(__DIR__ . '/PMContainer.php');
    }

    add_module_configuration(
        'FA_PM',
        'ksf_FA_ProjectManagement',
        'Project Management',
        '1.0.0',
        'KSFII Development Team',
        'Project',
        ['ksfraser/ksf-project-management']
    );
}