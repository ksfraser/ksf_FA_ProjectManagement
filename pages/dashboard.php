<?php
/**
 * Project Dashboard
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once(__DIR__ . "/../includes/pm_db.inc");
include_once(__DIR__ . "/../includes/pm_ui.inc");

page(_("Project Dashboard"), false, false, "", "");

start_table(TABLESTYLE_NOBORDER);
start_row();
pm_navigation_menu();
end_row();
end_table();

echo '<br>';

$dashboard_items = [
    'total_projects' => get_pm_project_count(),
    'active_projects' => get_pm_project_count('Active'),
    'pending_tasks' => get_pm_task_count('Not Started'),
    'overdue_tasks' => get_pm_overdue_task_count(),
];

display_pm_dashboard_stats($dashboard_items);
display_pm_recent_activities();

page_end();