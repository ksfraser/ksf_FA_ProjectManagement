<?php
/**
 * Projects List
 */

$path_to_root = "../..";
include_once($path_to_root . "/includes/session.inc");
include_once($path_to_root . "/includes/ui.inc");

include_once(__DIR__ . "/../includes/pm_db.inc");
include_once(__DIR__ . "/../includes/pm_ui.inc");

if (!$session->check_access('PM_VIEW_PROJECT')) {
    display_error("Access denied");
    exit;
}

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
    echo '<td ' . get_pm_status_class($project['status']) . '>' . $project['status'] . '</td>';
    echo '<td ' . get_pm_priority_class($project['priority']) . '>' . $project['priority'] . '</td>';
    echo '<td>' . $project['start_date'] . '</td>';
    echo '<td>' . ($project['end_date'] ?: '-') . '</td>';
    echo '<td>';
    echo '<a href="?section=projects&action=view&project_id=' . $project['project_id'] . '">View</a> | ';
    echo '<a href="?section=projects&action=edit&project_id=' . $project['project_id'] . '">Edit</a>';
    echo '</td>';
    echo '</tr>';
    $i++;
}

end_table();

echo '<br><a href="?section=projects&action=add" class="button">' . _("New Project") . '</a>';

page_end();