<?php
/**
 * Project Gantt Chart
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

page(_("Gantt Chart"), false, false, "", "");

start_table(TABLESTYLE_NOBORDER);
start_row();
pm_navigation_menu();
end_row();
end_table();

echo '<br>';

$projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

if (!$projectId) {
    echo '<div class="message">';
    echo '<p>Please select a project to view its Gantt chart.</p>';
    echo '</div>';
    
    $projects = get_pm_projects();
    echo '<form method="get">';
    echo '<input type="hidden" name="section" value="gantt">';
    echo '<label>Select Project: </label>';
    echo '<select name="project_id" onchange="this.form.submit()">';
    echo '<option value="">-- Select Project --</option>';
    while ($project = db_fetch_assoc($projects)) {
        $selected = ($project['project_id'] === $projectId) ? ' selected' : '';
        echo '<option value="' . $project['project_id'] . '"' . $selected . '>';
        echo htmlspecialchars($project['name']);
        echo '</option>';
    }
    echo '</select>';
    echo '</form>';
    
    page_end();
    return;
}

echo '<div class="toolbar">';
echo '<a href="?section=gantt&project_id=' . $projectId . '&format=json" class="button" target="_blank">Export JSON</a>';
echo '<a href="?section=gantt&project_id=' . $projectId . '&format=fullcalendar" class="button" target="_blank">FullCalendar</a>';
echo '</div>';

echo '<h3>Project Gantt Chart</h3>';

require_once __DIR__ . '/../../ksf_Gantt/src/Ksfraser/Gantt/Entity/GanttTask.php';
require_once __DIR__ . '/../../ksf_Gantt/src/Ksfraser/Gantt/Entity/GanttChart.php';
require_once __DIR__ . '/../../ksf_Gantt/src/Ksfraser/Gantt/Service/GanttRenderer.php';

use Ksfraser\Gantt\Entity\GanttTask;
use Ksfraser\Gantt\Entity\GanttChart;
use Ksfraser\Gantt\Service\GanttRenderer;

$project = get_pm_project($projectId);
if (!$project) {
    display_error("Project not found");
    page_end();
    return;
}

$chart = new GanttChart($project['project_id'], $project['name']);

$tasksResult = get_pm_tasks($projectId);
while ($task = db_fetch_assoc($tasksResult)) {
    $ganttTask = new GanttTask(
        $task['task_id'],
        $task['name'],
        $task['start_date'] ? new DateTime($task['start_date']) : null,
        $task['end_date'] ? new DateTime($task['end_date']) : null
    );
    
    $ganttTask->setProgress((float) $task['progress']);
    $ganttTask->setStatus(strtolower(str_replace(' ', '_', $task['status'])));
    $ganttTask->setPriority(strtolower($task['priority']));
    $ganttTask->setAssignee($task['assigned_to']);
    $ganttTask->setEstimatedHours((float) $task['estimated_hours']);
    
    if (!empty($task['parent_task_id'])) {
        $ganttTask->setParentId($task['parent_task_id']);
    }
    
    if (!empty($task['dependencies'])) {
        $deps = explode(',', $task['dependencies']);
        foreach ($deps as $dep) {
            $dep = trim($dep);
            if ($dep) {
                $ganttTask->addDependency($dep);
            }
        }
    }
    
    $chart->addTask($ganttTask);
}

if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo $chart->toJson();
    return;
}

if (isset($_GET['format']) && $_GET['format'] === 'fullcalendar') {
    $renderer = new GanttRenderer();
    $events = $renderer->toFullCalendar($chart);
    header('Content-Type: application/json');
    echo json_encode($events, JSON_PRETTY_PRINT);
    return;
}

$renderer = new GanttRenderer([
    'dayWidth' => 50,
    'rowHeight' => 45,
    'headerHeight' => 60,
    'sidebarWidth' => 280,
]);

echo $renderer->renderHtml($chart);

echo '<br><br>';
echo '<h4>Task Legend</h4>';
echo '<table class="table">';
echo '<tr><td style="background:#3b82f6; width:20px; height:20px;"></td><td>Pending</td></tr>';
echo '<tr><td style="background:#f59e0b; width:20px; height:20px;"></td><td>In Progress</td></tr>';
echo '<tr><td style="background:#22c55e; width:20px; height:20px;"></td><td>Completed</td></tr>';
echo '<tr><td style="background:#ef4444; width:20px; height:20px;"></td><td>Overdue</td></tr>';
echo '<tr><td style="background:#3b82f6; transform:rotate(45deg); width:15px; height:15px;"></td><td>Milestone</td></tr>';
echo '</table>';

start_table(TABLESTYLE);
echo '<tr><th colspan="6">' . _("Task List") . '</th></tr>';
echo '<tr>';
echo '<th>' . _("Task") . '</th>';
echo '<th>' . _("Start") . '</th>';
echo '<th>' . _("End") . '</th>';
echo '<th>' . _("Days") . '</th>';
echo '<th>' . _("Progress") . '</th>';
echo '<th>' . _("Status") . '</th>';
echo '</tr>';

$tasks = get_pm_tasks($projectId);
while ($task = db_fetch_assoc($tasks)) {
    $start = $task['start_date'] ? new DateTime($task['start_date']) : null;
    $end = $task['end_date'] ? new DateTime($task['end_date']) : null;
    $duration = ($start && $end) ? (int) $start->diff($end)->days : '-';
    
    echo '<tr>';
    echo '<td>' . $task['name'] . '</td>';
    echo '<td>' . ($task['start_date'] ?: '-') . '</td>';
    echo '<td>' . ($task['end_date'] ?: '-') . '</td>';
    echo '<td>' . $duration . '</td>';
    echo '<td>' . $task['progress'] . '%</td>';
    echo '<td>' . $task['status'] . '</td>';
    echo '</tr>';
}

end_table();

page_end();