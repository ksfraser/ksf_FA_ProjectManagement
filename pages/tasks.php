<?php
/**
 * Tasks List
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once(__DIR__ . "/../includes/pm_db.inc");
include_once(__DIR__ . "/../includes/pm_ui.inc");

if (!$session->check_access('PM_VIEW_TASKS')) {
    display_error("Access denied");
    exit;
}

page(_("Tasks"), false, false, "", "");

$projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

start_table(TABLESTYLE);

echo '<tr><th colspan="6">' . _("Tasks") . '</th></tr>';
echo '<tr>';
echo '<th>' . _("Task") . '</th>';
echo '<th>' . _("Project") . '</th>';
echo '<th>' . _("Status") . '</th>';
echo '<th>' . _("Progress") . '</th>';
echo '<th>' . _("Assigned To") . '</th>';
echo '<th>' . _("Actions") . '</th>';
echo '</tr>';

$tasks = get_pm_tasks($projectId);

while ($task = db_fetch_assoc($tasks)) {
    $overdueClass = ($task['end_date'] && $task['end_date'] < date('Y-m-d') && !in_array($task['status'], ['Completed', 'Cancelled'])) ? 'class="overdue"' : '';
    echo '<tr>';
    echo '<td>' . $task['name'] . '</td>';
    echo '<td>' . $task['project_name'] . '</td>';
    echo '<td ' . get_pm_status_class($task['status']) . '>' . $task['status'] . '</td>';
    echo '<td>';
    echo '<div style="width:100px; background:#eee; border:1px solid #ccc;">';
    echo '<div style="width:' . $task['progress'] . '%; background:#4caf50; height:15px;"></div>';
    echo '</div> ' . $task['progress'] . '%';
    echo '</td>';
    echo '<td>' . $task['assigned_to'] . '</td>';
    echo '<td>';
    echo '<a href="?section=tasks&action=edit&task_id=' . $task['task_id'] . '">Edit</a>';
    echo '</td>';
    echo '</tr>';
}

end_table();

echo '<br><a href="?section=tasks&action=add" class="button">' . _("New Task") . '</a>';

page_end();