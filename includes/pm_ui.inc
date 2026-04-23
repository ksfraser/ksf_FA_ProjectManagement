<?php
/**
 * FA PM UI Helper Functions
 *
 * @package FA_PM
 */

/**
 * PM Navigation Menu
 */
function pm_navigation_menu(): void
{
    echo '<ul class="tabs">';
    echo '<li><a href="?section=dashboard">' . _("Dashboard") . '</a></li>';
    echo '<li><a href="?section=projects">' . _("Projects") . '</a></li>';
    echo '<li><a href="?section=tasks">' . _("Tasks") . '</a></li>';
    echo '<li><a href="?section=team">' . _("Team") . '</a></li>';
    echo '<li><a href="?section=reports">' . _("Reports") . '</a></li>';
    echo '<li><a href="?section=settings">' . _("Settings") . '</a></li>';
    echo '</ul>';
}

/**
 * Display PM Dashboard Statistics
 */
function display_pm_dashboard_stats(array $stats): void
{
    start_table(TABLESTYLE);

    echo '<tr>';
    echo '<th class="headup" colspan="4">' . _("Project Management Dashboard") . '</th>';
    echo '</tr>';

    echo '<tr>';
    display_pm_stat_cell(_("Total Projects"), $stats['total_projects'] ?? 0, 'total_projects');
    display_pm_stat_cell(_("Active Projects"), $stats['active_projects'] ?? 0, 'active_projects');
    display_pm_stat_cell(_("Pending Tasks"), $stats['pending_tasks'] ?? 0, 'pending_tasks');
    display_pm_stat_cell(_("Overdue Tasks"), $stats['overdue_tasks'] ?? 0, 'overdue_tasks');
    echo '</tr>';

    end_table();
}

/**
 * Display individual stat cell
 */
function display_pm_stat_cell(string $label, int $value, string $type): void
{
    $icon = match ($type) {
        'total_projects' => '📁',
        'active_projects' => '✅',
        'pending_tasks' => '⏳',
        'overdue_tasks' => '⚠️',
        default => '📊',
    };

    echo '<td class="noplay" style="text-align:center; padding: 15px;">';
    echo '<div style="font-size: 24px;">' . $icon . '</div>';
    echo '<div style="font-size: 28px; font-weight: bold;">' . $value . '</div>';
    echo '<div style="color: #666;">' . $label . '</div>';
    echo '</td>';
}

/**
 * Display Recent Activities
 */
function display_pm_recent_activities(): void
{
    $activities = get_pm_recent_activities(5);

    if (empty($activities)) {
        return;
    }

    echo '<br>';
    start_table(TABLESTYLE);

    echo '<tr><th class="headup" colspan="3">' . _("Recent Activities") . '</th></tr>';
    echo '<tr><th>' . _("Date") . '</th><th>' . _("Action") . '</th><th>' . _("Details") . '</th></tr>';

    foreach ($activities as $activity) {
        echo '<tr>';
        echo '<td>' . $activity['created_at'] . '</td>';
        echo '<td>' . $activity['action'] . '</td>';
        echo '<td>' . $activity['details'] . '</td>';
        echo '</tr>';
    }

    end_table();
}

/**
 * Priority Select
 */
function sel_priority(string $selected = 'Medium'): string
{
    $priorities = ['Low', 'Medium', 'High', 'Critical'];
    $html = '<select name="priority">';

    foreach ($priorities as $priority) {
        $selectedAttr = ($priority === $selected) ? ' selected' : '';
        $html .= '<option value="' . $priority . '"' . $selectedAttr . '>' . _($priority) . '</option>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Project Status Select
 */
function sel_project_status(string $selected = 'Planning'): string
{
    $statuses = [
        'Planning',
        'Active',
        'On Hold',
        'Completed',
        'Cancelled'
    ];
    $html = '<select name="status">';

    foreach ($statuses as $status) {
        $selectedAttr = ($status === $selected) ? ' selected' : '';
        $html .= '<option value="' . $status . '"' . $selectedAttr . '>' . _($status) . '</option>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Task Status Select
 */
function sel_task_status(string $selected = 'Not Started'): string
{
    $statuses = [
        'Not Started',
        'In Progress',
        'On Hold',
        'Completed',
        'Cancelled'
    ];
    $html = '<select name="status">';

    foreach ($statuses as $status) {
        $selectedAttr = ($status === $selected) ? ' selected' : '';
        $html .= '<option value="' . $status . '"' . $selectedAttr . '>' . _($status) . '</option>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Project Select Dropdown
 */
function sel_project(array $projects, string $selected = ''): string
{
    $html = '<select name="project_id">';
    $html .= '<option value="">-- Select Project --</option>';

    while ($project = db_fetch_assoc($projects)) {
        $selectedAttr = ($project['project_id'] === $selected) ? ' selected' : '';
        $html .= '<option value="' . $project['project_id'] . '"' . $selectedAttr . '>';
        $html .= htmlspecialchars($project['name']);
        $html .= '</option>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Customer Select Dropdown
 */
function sel_customer(string $selected = ''): string
{
    $sql = "SELECT debtor_no, name FROM " . TB_PREF . "debtors_master ORDER BY name";
    $result = db_query($sql);

    $html = '<select name="customer_id">';
    $html .= '<option value="">-- None --</option>';

    while ($customer = db_fetch_assoc($result)) {
        $selectedAttr = ($customer['debtor_no'] === $selected) ? ' selected' : '';
        $html .= '<option value="' . $customer['debtor_no'] . '"' . $selectedAttr . '>';
        $html .= htmlspecialchars($customer['name']);
        $html .= '</option>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Project Type Select
 */
function sel_project_type(string $selected = ''): string
{
    $sql = "SELECT id, name FROM " . TB_PREF . "fa_pm_project_types WHERE inactive = 0 ORDER BY sort_order, name";
    $result = db_query($sql);

    $html = '<select name="project_type_id">';
    $html .= '<option value="">-- None --</option>';

    while ($type = db_fetch_assoc($result)) {
        $selectedAttr = ($type['id'] === $selected) ? ' selected' : '';
        $html .= '<option value="' . $type['id'] . '"' . $selectedAttr . '>';
        $html .= htmlspecialchars($type['name']);
        $html .= '</option>';
    }

    $html .= '</select>';
    return $html;
}

/**
 * Get priority color class
 */
function get_pm_priority_class(string $priority): string
{
    return match ($priority) {
        'Critical' => 'class="overdue"',
        'High' => 'class="alert"',
        'Medium' => 'class="warning"',
        default => '',
    };
}

/**
 * Get status color class
 */
function get_pm_status_class(string $status): string
{
    return match ($status) {
        'Completed' => 'class="success"',
        'Active', 'In Progress' => 'class="active"',
        'On Hold' => 'class="warning"',
        'Cancelled' => 'class="overdue"',
        default => '',
    };
}