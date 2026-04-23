<?php
/**
 * FA_PM API Controller
 *
 * Main controller for FA_PM module routes
 *
 * @package FA_PM
 * @version 1.0.0
 * @author KSFII Development Team
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");
include_once($path_to_root . "/includes/db.inc");

include_once(__DIR__ . "/includes/pm_db.inc");
include_once(__DIR__ . "/includes/pm_ui.inc");

$section = isset($_GET['section']) ? $_GET['section'] : 'dashboard';
$action = isset($_GET['action']) ? $_GET['action'] : 'list';

if (!$session->check_access('PM_VIEW_PROJECT')) {
    display_error("Access denied");
    exit;
}

function fa_pm_route(string $section, string $action): void
{
    switch ($section) {
        case 'dashboard':
            fa_pm_dashboard($action);
            break;
        case 'projects':
            fa_pm_projects($action);
            break;
        case 'tasks':
            fa_pm_tasks($action);
            break;
        case 'team':
            fa_pm_team($action);
            break;
        case 'reports':
            fa_pm_reports($action);
            break;
        case 'settings':
            fa_pm_settings($action);
            break;
        default:
            fa_pm_dashboard('list');
    }
}

function fa_pm_dashboard(string $action): void
{
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
}

function fa_pm_projects(string $action): void
{
    switch ($action) {
        case 'list':
            fa_pm_list_projects();
            break;
        case 'edit':
            fa_pm_edit_project();
            break;
        case 'view':
            fa_pm_view_project();
            break;
        case 'add':
            fa_pm_add_project();
            break;
        case 'delete':
            fa_pm_delete_project();
            break;
        default:
            fa_pm_list_projects();
    }
}

function fa_pm_list_projects(): void
{
    page(_("Projects"), false, false, "", "");

    $search = isset($_POST['Search']) ? $_POST['Search'] : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : '';

    start_table(TABLESTYLE);

    echo '<tr><th colspan="7">' . _("Projects") . '</th></tr>';
    echo '<tr>';
    echo '<th>' . _("Project") . '</th>';
    echo '<th>' . _("Manager") . '</th>';
    echo '<th>' . _("Status") . '</th>';
    echo '<th>' . _("Priority") . '</th>';
    echo '<th>' . _("Start Date") . '</th>';
    echo '<th>' . _("End Date") . '</th>';
    echo '<th>' . _("Actions") . '</th>';
    echo '</tr>';

    $projects = get_pm_projects($search, $filter_status);
    $i = 0;

    while ($project = db_fetch_assoc($projects)) {
        echo '<tr class="' . ($i % 2 == 0 ? 'evenrow' : 'oddrow') . '">';
        echo '<td>' . $project['name'] . '</td>';
        echo '<td>' . $project['project_manager'] . '</td>';
        echo '<td>' . $project['status'] . '</td>';
        echo '<td>' . $project['priority'] . '</td>';
        echo '<td>' . $project['start_date'] . '</td>';
        echo '<td>' . $project['end_date'] . '</td>';
        echo '<td>';
        echo '<a href="?section=projects&action=view&project_id=' . $project['project_id'] . '">View</a> | ';
        echo '<a href="?section=projects&action=edit&project_id=' . $project['project_id'] . '">Edit</a>';
        echo '</td>';
        echo '</tr>';
        $i++;
    }

    end_table();

    echo '<br><a href="?section=projects&action=add">' . _("New Project") . '</a>';

    page_end();
}

function fa_pm_add_project(): void
{
    fa_pm_edit_project();
}

function fa_pm_edit_project(): void
{
    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';
    $isNew = empty($projectId);

    page($isNew ? _("New Project") : _("Edit Project"), true, false, "", "");

    if (isset($_POST['SAVE'])) {
        $projectData = [
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'startDate' => $_POST['start_date'],
            'endDate' => $_POST['end_date'] ?: null,
            'budget' => $_POST['budget'] ?? 0,
            'customerId' => $_POST['customer_id'] ?? '',
            'projectManager' => $_POST['project_manager'],
            'priority' => $_POST['priority'] ?? 'Medium',
            'status' => $_POST['status'] ?? 'Planning',
        ];

        $container = fa_pm_get_container();
        $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);

        try {
            if ($isNew) {
                $service->createProject($projectData);
                display_notification("Project created successfully");
            } else {
                $service->updateProject($projectId, $projectData);
                display_notification("Project updated successfully");
            }
        } catch (\Ksfraser\ProjectManagement\Exception\ProjectException $e) {
            display_error($e->getMessage());
        }
    }

    $project = null;
    if (!$isNew) {
        $container = fa_pm_get_container();
        $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);
        try {
            $project = $service->getProject($projectId);
        } catch (\Ksfraser\ProjectManagement\Exception\ProjectNotFoundException $e) {
            display_error($e->getMessage());
            return;
        }
    }

    start_form();
    start_table(TABLESTYLE);

    table_header($isNew ? _("New Project") : _("Edit Project"));

    row(label_cell(_("Project Name")));
    cell(text_input('name', $project?->getName() ?? '', 50));
    end_row();

    row(label_cell(_("Description")));
    cell(textarea('description', $project?->getDescription() ?? '', 50, 4));
    end_row();

    row(label_cell(_("Start Date")));
    cell(date_input('start_date', $project?->getStartDate()?->format('Y-m-d') ?? date('Y-m-d')));
    end_row();

    row(label_cell(_("End Date")));
    cell(date_input('end_date', $project?->getEndDate()?->format('Y-m-d') ?? ''));
    end_row();

    row(label_cell(_("Budget")));
    cell(amount_input($project?->getBudget() ?? 0, 'budget'));
    end_row();

    row(label_cell(_("Project Manager")));
    cell(text_input('project_manager', $project?->getProjectManager() ?? ''));
    end_row();

    row(label_cell(_("Priority")));
    cell(sel_priority($project?->getPriority() ?? 'Medium'));
    end_row();

    row(label_cell(_("Status")));
    cell(sel_project_status($project?->getStatus() ?? 'Planning'));
    end_row();

    end_table();

    submit_row('SAVE', _("Save"), true, '', 'default');

    end_form();
    page_end();
}

function fa_pm_view_project(): void
{
    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

    if ($projectId === '') {
        display_error("No project specified");
        return;
    }

    $container = fa_pm_get_container();
    $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);

    try {
        $project = $service->getProject($projectId);
    } catch (\Ksfraser\ProjectManagement\Exception\ProjectNotFoundException $e) {
        display_error($e->getMessage());
        return;
    }

    $tasks = $service->getProjectTasks($projectId);
    $team = $service->getProjectTeam($projectId);

    page(_("Project Details"), true, false, "", "");

    display_heading($project->getName());

    start_table(TABLESTYLE);

    row(label_cell(_("Manager")), cell($project->getProjectManager()));
    row(label_cell(_("Status")), cell($project->getStatus()));
    row(label_cell(_("Priority")), cell($project->getPriority()));
    row(label_cell(_("Start Date")), cell($project->getStartDate()->format('Y-m-d')));
    row(label_cell(_("End Date")), cell($project->getEndDate()?->format('Y-m-d') ?? '-'));
    row(label_cell(_("Budget")), cell(number_format($project->getBudget(), 2)));
    row(label_cell(_("Description")), cell($project->getDescription()));

    end_table();

    echo '<br><h3>' . _("Tasks") . '</h3>';
    start_table(TABLESTYLE);
    echo '<tr><th>Task</th><th>Status</th><th>Progress</th><th>Assigned To</th></tr>';
    foreach ($tasks as $task) {
        echo '<tr>';
        echo '<td>' . $task->getName() . '</td>';
        echo '<td>' . $task->getStatus() . '</td>';
        echo '<td>' . $task->getProgress() . '%</td>';
        echo '<td>' . $task->getAssignedTo() . '</td>';
        echo '</tr>';
    }
    end_table();

    echo '<br><h3>' . _("Team") . '</h3>';
    start_table(TABLESTYLE);
    echo '<tr><th>Employee</th><th>Role</th><th>Allocation</th></tr>';
    foreach ($team as $member) {
        echo '<tr>';
        echo '<td>' . $member['first_name'] . ' ' . $member['last_name'] . '</td>';
        echo '<td>' . $member['role'] . '</td>';
        echo '<td>' . $member['allocation_percentage'] . '%</td>';
        echo '</tr>';
    }
    end_table();

    echo '<br><a href="?section=projects&action=edit&project_id=' . $projectId . '">' . _("Edit Project") . '</a>';

    page_end();
}

function fa_pm_delete_project(): void
{
    $projectId = $_POST['project_id'] ?? $_GET['project_id'] ?? '';

    $container = fa_pm_get_container();
    $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);

    try {
        $service->deleteProject($projectId);
        display_notification("Project deleted successfully");
    } catch (\Ksfraser\ProjectManagement\Exception\ProjectNotFoundException $e) {
        display_error($e->getMessage());
    }

    fa_pm_list_projects();
}

function fa_pm_tasks(string $action): void
{
    switch ($action) {
        case 'list':
            fa_pm_list_tasks();
            break;
        case 'edit':
            fa_pm_edit_task();
            break;
        case 'add':
            fa_pm_add_task();
            break;
        default:
            fa_pm_list_tasks();
    }
}

function fa_pm_list_tasks(): void
{
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
        echo '<tr>';
        echo '<td>' . $task['name'] . '</td>';
        echo '<td>' . $task['project_name'] . '</td>';
        echo '<td>' . $task['status'] . '</td>';
        echo '<td>' . $task['progress'] . '%</td>';
        echo '<td>' . $task['assigned_to'] . '</td>';
        echo '<td>';
        echo '<a href="?section=tasks&action=edit&task_id=' . $task['task_id'] . '">Edit</a>';
        echo '</td>';
        echo '</tr>';
    }

    end_table();

    page_end();
}

function fa_pm_add_task(): void
{
    fa_pm_edit_task();
}

function fa_pm_edit_task(): void
{
    $taskId = isset($_GET['task_id']) ? $_GET['task_id'] : '';
    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';
    $isNew = empty($taskId);

    page($isNew ? _("New Task") : _("Edit Task"), true, false, "", "");

    if (isset($_POST['SAVE'])) {
        $taskData = [
            'projectId' => $_POST['project_id'],
            'name' => $_POST['name'],
            'description' => $_POST['description'] ?? '',
            'assignedTo' => $_POST['assigned_to'] ?? '',
            'startDate' => $_POST['start_date'] ?: null,
            'endDate' => $_POST['end_date'] ?: null,
            'estimatedHours' => $_POST['estimated_hours'] ?? 0,
            'priority' => $_POST['priority'] ?? 'Medium',
            'status' => $_POST['status'] ?? 'Not Started',
        ];

        $container = fa_pm_get_container();
        $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);

        try {
            if ($isNew) {
                $service->createTask($taskData);
                display_notification("Task created successfully");
            } else {
                $service->updateTask($taskId, $taskData);
                display_notification("Task updated successfully");
            }
        } catch (\Ksfraser\ProjectManagement\Exception\ValidationException $e) {
            display_error($e->getMessage());
        }
    }

    $task = null;
    if (!$isNew) {
        $container = fa_pm_get_container();
        $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);
        try {
            $task = $service->getTask($taskId);
            $projectId = $task->getProjectId();
        } catch (\Ksfraser\ProjectManagement\Exception\TaskNotFoundException $e) {
            display_error($e->getMessage());
            return;
        }
    }

    $projects = get_pm_projects();

    start_form();
    start_table(TABLESTYLE);

    table_header($isNew ? _("New Task") : _("Edit Task"));

    row(label_cell(_("Project")));
    cell(sel_project($projects, $projectId));
    end_row();

    row(label_cell(_("Task Name")));
    cell(text_input('name', $task?->getName() ?? '', 50));
    end_row();

    row(label_cell(_("Description")));
    cell(textarea('description', $task?->getDescription() ?? '', 50, 4));
    end_row();

    row(label_cell(_("Assigned To")));
    cell(text_input('assigned_to', $task?->getAssignedTo() ?? '', 30));
    end_row();

    row(label_cell(_("Start Date")));
    cell(date_input('start_date', $task?->getStartDate()?->format('Y-m-d') ?? ''));
    end_row();

    row(label_cell(_("End Date")));
    cell(date_input('end_date', $task?->getEndDate()?->format('Y-m-d') ?? ''));
    end_row();

    row(label_cell(_("Estimated Hours")));
    cell(text_input('estimated_hours', $task?->getEstimatedHours() ?? 0, 10));
    end_row();

    row(label_cell(_("Priority")));
    cell(sel_priority($task?->getPriority() ?? 'Medium'));
    end_row();

    row(label_cell(_("Status")));
    cell(sel_task_status($task?->getStatus() ?? 'Not Started'));
    end_row();

    end_table();

    submit_row('SAVE', _("Save"), true, '', 'default');

    end_form();
    page_end();
}

function fa_pm_team(string $action): void
{
    page(_("Project Team"), false, false, "", "");

    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

    if ($projectId) {
        $container = fa_pm_get_container();
        $service = $container->get(\Ksfraser\ProjectManagement\ProjectService::class);
        $team = $service->getProjectTeam($projectId);

        start_table(TABLESTYLE);
        echo '<tr><th>Employee</th><th>Role</th><th>Email</th><th>Allocation</th></tr>';
        foreach ($team as $member) {
            echo '<tr>';
            echo '<td>' . $member['first_name'] . ' ' . $member['last_name'] . '</td>';
            echo '<td>' . $member['role'] . '</td>';
            echo '<td>' . $member['email'] . '</td>';
            echo '<td>' . $member['allocation_percentage'] . '%</td>';
            echo '</tr>';
        }
        end_table();
    } else {
        echo '<p>Select a project to view team members.</p>';
    }

    page_end();
}

function fa_pm_reports(string $action): void
{
    page(_("Project Reports"), false, false, "", "");

    echo '<h3>Available Reports</h3>';
    echo '<ul>';
    echo '<li><a href="?section=reports&type=projects">Project Summary</a></li>';
    echo '<li><a href="?section=reports&type=tasks">Task Analysis</a></li>';
    echo '<li><a href="?section=reports&type=utilization">Resource Utilization</a></li>';
    echo '</ul>';

    page_end();
}

function fa_pm_settings(string $action): void
{
    page(_("Project Management Settings"), true, false, "", "");

    echo '<h3>PM Settings</h3>';
    echo '<p>Settings page coming soon</p>';

    page_end();
}

fa_pm_route($section, $action);