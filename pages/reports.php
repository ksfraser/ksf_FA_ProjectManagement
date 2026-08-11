<?php
/**
 * Project Reports
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once(__DIR__ . "/../includes/pm_db.inc");
include_once(__DIR__ . "/../includes/pm_ui.inc");

if (!$session->check_access('PM_VIEW_REPORTS')) {
    display_error("Access denied");
    exit;
}

page(_("Project Reports"), false, false, "", "");

$reportType = isset($_GET['type']) ? $_GET['type'] : 'summary';

echo '<h3>' . _("Available Reports") . '</h3>';
echo '<ul>';
echo '<li><a href="?type=summary">' . ($reportType === 'summary' ? '<b>' : '') . 'Project Summary' . ($reportType === 'summary' ? '</b>' : '') . '</a></li>';
echo '<li><a href="?type=tasks">' . ($reportType === 'tasks' ? '<b>' : '') . 'Task Analysis' . ($reportType === 'tasks' ? '</b>' : '') . '</a></li>';
echo '<li><a href="?type=utilization">' . ($reportType === 'utilization' ? '<b>' : '') . 'Resource Utilization' . ($reportType === 'utilization' ? '</b>' : '') . '</a></li>';
echo '</ul>';

switch ($reportType) {
    case 'summary':
        echo '<h3>' . _("Project Summary") . '</h3>';
        break;

    case 'tasks':
        echo '<h3>' . _("Task Analysis") . '</h3>';
        break;

    case 'utilization':
        include __DIR__ . '/../pages/utilization.php';
        break;
}

page_end();