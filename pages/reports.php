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

echo '<hr>';

switch ($reportType) {
    case 'summary':
        echo '<h3>' . _("Project Summary") . '</h3>';
        $projects = get_pm_projects();
        start_table(TABLESTYLE);
        echo '<tr><th>Status</th><th>Count</th></tr>';

        $statusCounts = [];
        db_data_seek($projects, 0);
        while ($p = db_fetch_assoc($projects)) {
            $status = $p['status'];
            if (!isset($statusCounts[$status])) {
                $statusCounts[$status] = 0;
            }
            $statusCounts[$status]++;
        }

        foreach ($statusCounts as $status => $count) {
            echo '<tr><td>' . $status . '</td><td>' . $count . '</td></tr>';
        }
        end_table();
        break;

    case 'tasks':
        echo '<h3>' . _("Task Analysis") . '</h3>';
        echo '<p>Task status breakdown coming soon.</p>';
        break;

    case 'utilization':
        echo '<h3>' . _("Resource Utilization") . '</h3>';
        echo '<p>Resource utilization report coming soon.</p>';
        break;
}

page_end();