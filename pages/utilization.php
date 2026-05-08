<?php
/**
 * Resource Utilization Report
 *
 * Uses ksf_Gantt for resource tracking and utilization calculations
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

page(_("Resource Utilization"), false, false, "", "");

start_table(TABLESTYLE_NOBORDER);
start_row();
pm_navigation_menu();
end_row();
end_table();

echo '<br>';

$projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

$projects = get_pm_projects();
echo '<form method="get">';
echo '<input type="hidden" name="section" value="reports">';
echo '<input type="hidden" name="type" value="utilization">';
echo '<label>Select Project: </label>';
echo '<select name="project_id" onchange="this.form.submit()">';
echo '<option value="">All Projects</option>';
while ($project = db_fetch_assoc($projects)) {
    $selected = ($project['project_id'] === $projectId) ? ' selected' : '';
    echo '<option value="' . $project['project_id'] . '"' . $selected . '>';
    echo htmlspecialchars($project['name']);
    echo '</option>';
}
echo '</select>';
echo '</form>';

if (!$projectId) {
    echo '<p>Select a project to view resource utilization.</p>';
    page_end();
    return;
}

require_once __DIR__ . '/../../ksf_Gantt/src/Ksfraser/Gantt/Entity/GanttTask.php';
require_once __DIR__ . '/../../ksf_Gantt/src/Ksfraser/Gantt/Entity/GanttChart.php';
require_once __DIR__ . '/../../ksf_Gantt/src/Ksfraser/Gantt/Service/ResourceUtilization.php';

use Ksfraser\Gantt\Entity\GanttTask;
use Ksfraser\Gantt\Entity\GanttChart;
use Ksfraser\Gantt\Service\ResourceUtilization;

$project = get_pm_project($projectId);
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
    $ganttTask->setAssignee($task['assigned_to']);
    $ganttTask->setEstimatedHours((float) $task['estimated_hours']);
    $ganttTask->setActualHours((float) $task['actual_hours']);
    
    $chart->addTask($ganttTask);
}

$utilService = new ResourceUtilization([
    'maxDailyHours' => 8.0,
    'warningThreshold' => 0.7,
    'overloadThreshold' => 1.0,
]);

echo '<h3>Resource Workload</h3>';

$workload = $utilService->getResourceWorkload($chart);

if (empty($workload)) {
    echo '<p>No tasks with assignees found.</p>';
} else {
    echo '<p><a href="?section=reports&type=utilization&project_id=' . $projectId . '&export=csv">Download CSV</a></p>';
    
    start_table(TABLESTYLE);
    echo '<tr>';
    echo '<th>Resource</th>';
    echo '<th>Tasks</th>';
    echo '<th>Completed</th>';
    echo '<th>In Progress</th>';
    echo '<th>Pending</th>';
    echo '<th>Est. Hours</th>';
    echo '<th>Actual Hours</th>';
    echo '<th>Efficiency</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    
    foreach ($workload as $assignee => $data) {
        $statusClass = match($data['efficiency'] > 1.1 ? 'overdue' : ($data['efficiency'] >= 0.9 ? 'active' : 'warning'));
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($assignee) . '</td>';
        echo '<td>' . $data['task_count'] . '</td>';
        echo '<td>' . $data['completed'] . '</td>';
        echo '<td>' . $data['in_progress'] . '</td>';
        echo '<td>' . $data['pending'] . '</td>';
        echo '<td>' . number_format($data['estimated_hours'], 1) . '</td>';
        echo '<td>' . number_format($data['actual_hours'], 1) . '</td>';
        echo '<td>' . number_format($data['efficiency'] * 100, 0) . '%</td>';
        echo '<td class="' . $statusClass . '">';
        
        if ($data['efficiency'] > 1.0) {
            echo 'Overloaded';
        } elseif ($data['efficiency'] >= 0.9) {
            echo 'Optimal';
        } else {
            echo 'Underutilized';
        }
        echo '</td>';
        echo '</tr>';
    }
    end_table();
}

$startDate = isset($_GET['start']) ? new DateTime($_GET['start']) : null;
$endDate = isset($_GET['end']) ? new DateTime($_GET['end']) : null;

if ($startDate && $endDate) {
    echo '<br><h3>Utilization (' . $startDate->format('M d') . ' - ' . $endDate->format('M d') . ')</h3>';
    
    $utilization = $utilService->calculateUtilization($chart, $startDate, $endDate);
    
    start_table(TABLESTYLE);
    echo '<tr>';
    echo '<th>Resource</th>';
    echo '<th>Total Hours</th>';
    echo '<th>Available</th>';
    echo '<th>Utilization</th>';
    echo '<th>Status</th>';
    echo '</tr>';
    
    foreach ($utilization as $assignee => $data) {
        $utilPct = number_format($data['overall_utilization'] * 100, 0);
        $statusClass = match($data['status']) {
            'overloaded' => 'class="overdue"',
            'underutilized' => 'class="warning"',
            default => '',
        };
        
        echo '<tr>';
        echo '<td>' . htmlspecialchars($assignee) . '</td>';
        echo '<td>' . number_format($data['total_hours'], 1) . '</td>';
        echo '<td>' . number_format($data['available_hours'], 1) . '</td>';
        echo '<td>' . $utilPct . '%</td>';
        echo '<td ' . $statusClass . '>' . ucfirst($data['status']) . '</td>';
        echo '</tr>';
    }
    end_table();
    
    echo '<br><h3>Capacity Planning (Daily)</h3>';
    
    $capacity = $utilService->getCapacityPlanning($chart, $startDate, $endDate);
    
    start_table(TABLESTYLE);
    echo '<tr>';
    echo '<th>Date</th>';
    echo '<th>Total Hours</th>';
    echo '<th>Overloaded Resources</th>';
    echo '</tr>';
    
    foreach ($capacity as $date => $data) {
        $overloaded = array_filter($data['resources'], fn($r) => $r['utilization'] > 1.0);
        
        echo '<tr>';
        echo '<td>' . $date . '</td>';
        echo '<td>' . number_format($data['total_hours'], 1) . '</td>';
        echo '<td>' . count($overloaded) . '</td>';
        echo '</tr>';
    }
    end_table();
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="resource_utilization.csv"');
    echo $utilService->exportToCsv($chart, $startDate ?? null, $endDate ?? null);
    return;
}

echo '<br>';
echo '<form method="get">';
echo '<input type="hidden" name="section" value="reports">';
echo '<input type="hidden" name="type" value="utilization">';
echo '<input type="hidden" name="project_id" value="' . $projectId . '">';
echo '<label>Start: </label>';
echo '<input type="date" name="start" value="' . ($startDate ? $startDate->format('Y-m-d') : date('Y-m-01')) . '">';
echo '<label> End: </label>';
echo '<input type="date" name="end" value="' . ($endDate ? $endDate->format('Y-m-d') : date('Y-m-t')) . '">';
echo '<button type="submit">Calculate</button>';
echo '</form>';

page_end();