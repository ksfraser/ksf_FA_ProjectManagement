<?php
/**
 * Team Management
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once(__DIR__ . "/../includes/pm_db.inc");
include_once(__DIR__ . "/../includes/pm_ui.inc");

if (!$session->check_access('PM_VIEW_TEAM')) {
    display_error("Access denied");
    exit;
}

page(_("Project Team"), false, false, "", "");

$projectId = isset($_GET['project_id']) ? $_GET['project_id'] : '';

echo '<form method="get">';
echo '<input type="hidden" name="section" value="team">';
echo '<label for="project_id">' . _("Select Project") . ':</label> ';
$projects = get_pm_projects();
echo sel_project($projects, $projectId);
echo ' <input type="submit" value="' . _("Go") . '">';
echo '</form>';

echo '<br>';

if ($projectId) {
    $team = get_pm_project_team($projectId);

    if (empty($team)) {
        echo '<p>No team members assigned to this project.</p>';
    } else {
        start_table(TABLESTYLE);
        echo '<tr><th colspan="5">' . _("Team Members") . '</th></tr>';
        echo '<tr>';
        echo '<th>' . _("Name") . '</th>';
        echo '<th>' . _("Role") . '</th>';
        echo '<th>' . _("Email") . '</th>';
        echo '<th>' . _("Job Title") . '</th>';
        echo '<th>' . _("Allocation") . '</th>';
        echo '</tr>';

        foreach ($team as $member) {
            echo '<tr>';
            echo '<td>' . $member['first_name'] . ' ' . $member['last_name'] . '</td>';
            echo '<td>' . $member['role'] . '</td>';
            echo '<td>' . $member['email'] . '</td>';
            echo '<td>' . $member['job_title'] . '</td>';
            echo '<td>' . $member['allocation_percentage'] . '%</td>';
            echo '</tr>';
        }
        end_table();
    }
} else {
    echo '<p>Select a project to view team members.</p>';
}

page_end();