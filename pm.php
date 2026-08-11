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

function fa_pm_route($section, $action)
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

function fa_pm_dashboard($action)
{
    page(_("Project Dashboard"), false, false, "", "");

    start_table(TABLESTYLE_NOBORDER);
    start_row();
    pm_navigation_menu();
    end_row();
    end_table();

    echo '<br>';

    $dashboard_items = array(
        'total_projects' => get_pm_project_count(),
        'active_projects' => get_pm_project_count('Active'),
        'pending_tasks' => get_pm_task_count('Not Started'),
        'overdue_tasks' => get_pm_overdue_task_count(),
    );

    display_pm_dashboard_stats($dashboard_items);
    display_pm_recent_activities();

    page_end();
}

function fa_pm_projects($action)
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

function fa_pm_list_projects()
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

function fa_pm_add_project()
{
    fa_pm_edit_project();
}

function fa_pm_edit_project()
{
    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';
    $isNew = empty($projectId);

    page($isNew ? _("New Project") : _("Edit Project"), true, false, "", "");

    if (isset($_POST['SAVE'])) {
        $projectData = array(
            'name' => $_POST['name'],
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
            'startDate' => $_POST['start_date'],
            'endDate' => isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'budget' => isset($_POST['budget']) ? $_POST['budget'] : 0,
            'customerId' => isset($_POST['customer_id']) ? $_POST['customer_id'] : '',
            'projectManager' => $_POST['project_manager'],
            'priority' => isset($_POST['priority']) ? $_POST['priority'] : 'Medium',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'Planning',
        );

        $container = fa_pm_get_container();
        $service = $container->get('Ksfraser\ProjectManagement\ProjectService');

        try {
            if ($isNew) {
                $service->createProject($projectData);
                display_notification("Project created successfully");
            } else {
                $service->updateProject($projectId, $projectData);
                display_notification("Project updated successfully");
            }
        } catch (Exception $e) {
            display_error($e->getMessage());
        }
    }

    $project = null;
    if (!$isNew) {
        $container = fa_pm_get_container();
        $service = $container->get('Ksfraser\ProjectManagement\ProjectService');
        try {
            $project = $service->getProject($projectId);
        } catch (Exception $e) {
            display_error($e->getMessage());
            return;
        }
    }

    start_form();
    start_table(TABLESTYLE);

    table_header($isNew ? _("New Project") : _("Edit Project"));

    $projectName = $project ? $project->getName() : '';
    $projectDesc = $project ? $project->getDescription() : '';
    $projectStart = $project && $project->getStartDate() ? $project->getStartDate()->format('Y-m-d') : date('Y-m-d');
    $projectEnd = $project && $project->getEndDate() ? $project->getEndDate()->format('Y-m-d') : '';
    $projectBudget = $project ? $project->getBudget() : 0;
    $projectMgr = $project ? $project->getProjectManager() : '';
    $projectPriority = $project ? $project->getPriority() : 'Medium';
    $projectStatus = $project ? $project->getStatus() : 'Planning';

    row(label_cell(_("Project Name")));
    cell(text_input('name', $projectName, 50));
    end_row();

    row(label_cell(_("Description")));
    cell(textarea('description', $projectDesc, 50, 4));
    end_row();

    row(label_cell(_("Start Date")));
    cell(date_input('start_date', $projectStart));
    end_row();

    row(label_cell(_("End Date")));
    cell(date_input('end_date', $projectEnd));
    end_row();

    row(label_cell(_("Budget")));
    cell(amount_input($projectBudget, 'budget'));
    end_row();

    row(label_cell(_("Project Manager")));
    cell(text_input('project_manager', $projectMgr));
    end_row();

    row(label_cell(_("Priority")));
    cell(sel_priority($projectPriority));
    end_row();

    row(label_cell(_("Status")));
    cell(sel_project_status($projectStatus));
    end_row();

    end_table();

    submit_row('SAVE', _("Save"), true, '', 'default');

    end_form();
    page_end();
}

function fa_pm_view_project()
{
    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

    if ($projectId === '') {
        display_error("No project specified");
        return;
    }

    $container = fa_pm_get_container();
    $service = $container->get('Ksfraser\ProjectManagement\ProjectService');

    try {
        $project = $service->getProject($projectId);
    } catch (Exception $e) {
        display_error($e->getMessage());
        return;
    }

    $tasks = $service->getProjectTasks($projectId);
    $team = $service->getProjectTeam($projectId);

    page(_("Project Details"), true, false, "", "");

    display_heading($project->getName());

    start_table(TABLESTYLE);

    $endDate = $project->getEndDate();
    $endDateStr = $endDate ? $endDate->format('Y-m-d') : '-';

    row(label_cell(_("Manager")), cell($project->getProjectManager()));
    row(label_cell(_("Status")), cell($project->getStatus()));
    row(label_cell(_("Priority")), cell($project->getPriority()));
    row(label_cell(_("Start Date")), cell($project->getStartDate()->format('Y-m-d')));
    row(label_cell(_("End Date")), cell($endDateStr));
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

function fa_pm_delete_project()
{
    $projectId = isset($_POST['project_id']) ? $_POST['project_id'] : (isset($_GET['project_id']) ? $_GET['project_id'] : '');

    $container = fa_pm_get_container();
    $service = $container->get('Ksfraser\ProjectManagement\ProjectService');

    try {
        $service->deleteProject($projectId);
        display_notification("Project deleted successfully");
    } catch (Exception $e) {
        display_error($e->getMessage());
    }

    fa_pm_list_projects();
}

function fa_pm_tasks($action)
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

function fa_pm_list_tasks()
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

function fa_pm_add_task()
{
    fa_pm_edit_task();
}

function fa_pm_edit_task()
{
    $taskId = isset($_GET['task_id']) ? $_GET['task_id'] : '';
    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';
    $isNew = empty($taskId);

    page($isNew ? _("New Task") : _("Edit Task"), true, false, "", "");

    if (isset($_POST['SAVE'])) {
        $taskData = array(
            'projectId' => $_POST['project_id'],
            'name' => $_POST['name'],
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
            'assignedTo' => isset($_POST['assigned_to']) ? $_POST['assigned_to'] : '',
            'startDate' => isset($_POST['start_date']) && !empty($_POST['start_date']) ? $_POST['start_date'] : null,
            'endDate' => isset($_POST['end_date']) && !empty($_POST['end_date']) ? $_POST['end_date'] : null,
            'estimatedHours' => isset($_POST['estimated_hours']) ? $_POST['estimated_hours'] : 0,
            'priority' => isset($_POST['priority']) ? $_POST['priority'] : 'Medium',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'Not Started',
        );

        $container = fa_pm_get_container();
        $service = $container->get('Ksfraser\ProjectManagement\ProjectService');

        try {
            if ($isNew) {
                $service->createTask($taskData);
                display_notification("Task created successfully");
            } else {
                $service->updateTask($taskId, $taskData);
                display_notification("Task updated successfully");
            }
        } catch (Exception $e) {
            display_error($e->getMessage());
        }
    }

    $task = null;
    if (!$isNew) {
        $container = fa_pm_get_container();
        $service = $container->get('Ksfraser\ProjectManagement\ProjectService');
        try {
            $task = $service->getTask($taskId);
            $projectId = $task->getProjectId();
        } catch (Exception $e) {
            display_error($e->getMessage());
            return;
        }
    }

    $projects = get_pm_projects();

    start_form();
    start_table(TABLESTYLE);

    table_header($isNew ? _("New Task") : _("Edit Task"));

    $taskName = $task ? $task->getName() : '';
    $taskDesc = $task ? $task->getDescription() : '';
    $taskAssigned = $task ? $task->getAssignedTo() : '';
    $taskStart = $task && $task->getStartDate() ? $task->getStartDate()->format('Y-m-d') : '';
    $taskEnd = $task && $task->getEndDate() ? $task->getEndDate()->format('Y-m-d') : '';
    $taskHours = $task ? $task->getEstimatedHours() : 0;
    $taskPriority = $task ? $task->getPriority() : 'Medium';
    $taskStatus = $task ? $task->getStatus() : 'Not Started';

    row(label_cell(_("Project")));
    cell(sel_project($projects, $projectId));
    end_row();

    row(label_cell(_("Task Name")));
    cell(text_input('name', $taskName, 50));
    end_row();

    row(label_cell(_("Description")));
    cell(textarea('description', $taskDesc, 50, 4));
    end_row();

    row(label_cell(_("Assigned To")));
    cell(text_input('assigned_to', $taskAssigned, 30));
    end_row();

    row(label_cell(_("Start Date")));
    cell(date_input('start_date', $taskStart));
    end_row();

    row(label_cell(_("End Date")));
    cell(date_input('end_date', $taskEnd));
    end_row();

    row(label_cell(_("Estimated Hours")));
    cell(text_input('estimated_hours', $taskHours, 10));
    end_row();

    row(label_cell(_("Priority")));
    cell(sel_priority($taskPriority));
    end_row();

    row(label_cell(_("Status")));
    cell(sel_task_status($taskStatus));
    end_row();

    end_table();

    submit_row('SAVE', _("Save"), true, '', 'default');

    end_form();
    page_end();
}

function fa_pm_team($action)
{
    page(_("Project Team"), false, false, "", "");

    $projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

    if ($projectId) {
        $container = fa_pm_get_container();
        $service = $container->get('Ksfraser\ProjectManagement\ProjectService');
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

function fa_pm_reports($action)
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

function fa_pm_settings($action)
{
    page(_("Project Management Settings"), true, false, "", "");

    echo '<h3>PM Settings</h3>';
    echo '<p>Settings page coming soon</p>';

    page_end();
}

fa_pm_route($section, $action);