<?php
/**
 * FA PM Database Functions
 *
 * @package FA_PM
 */

/**
 * Get all projects
 */
function get_pm_projects(string $search = '', string $status = ''): object|false
{
    $sql = "SELECT p.*,
            e.first_name as manager_first_name, e.last_name as manager_last_name
            FROM " . TB_PREF . "fa_pm_projects p
            LEFT JOIN employees e ON p.project_manager = e.employee_id
            WHERE 1=1";

    $params = [];

    if ($search !== '') {
        $sql .= " AND (p.name LIKE " . db_escape('%' . $search . '%') . "
                 OR p.description LIKE " . db_escape('%' . $search . '%') . ")";
    }

    if ($status !== '') {
        $sql .= " AND p.status = " . db_escape($status);
    }

    $sql .= " ORDER BY p.start_date DESC";

    return db_query($sql, "Could not retrieve projects");
}

/**
 * Get project by ID
 */
function get_pm_project(string $projectId): ?array
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_pm_projects WHERE project_id = " . db_escape($projectId);
    $result = db_query($sql);

    if ($result === false) {
        return null;
    }

    return db_fetch_assoc($result);
}

/**
 * Get project count
 */
function get_pm_project_count(string $status = ''): int
{
    $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "fa_pm_projects";

    if ($status !== '') {
        $sql .= " WHERE status = " . db_escape($status);
    }

    $result = db_query($sql);
    $row = db_fetch_assoc($result);

    return (int) ($row['cnt'] ?? 0);
}

/**
 * Get all tasks
 */
function get_pm_tasks(string $projectId = ''): object|false
{
    $sql = "SELECT t.*, p.name as project_name
            FROM " . TB_PREF . "fa_pm_tasks t
            INNER JOIN " . TB_PREF . "fa_pm_projects p ON t.project_id = p.project_id
            WHERE 1=1";

    if ($projectId !== '') {
        $sql .= " AND t.project_id = " . db_escape($projectId);
    }

    $sql .= " ORDER BY t.project_id, t.parent_task_id, t.task_id";

    return db_query($sql, "Could not retrieve tasks");
}

/**
 * Get task by ID
 */
function get_pm_task(string $taskId): ?array
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_pm_tasks WHERE task_id = " . db_escape($taskId);
    $result = db_query($sql);

    if ($result === false) {
        return null;
    }

    return db_fetch_assoc($result);
}

/**
 * Get task count by status
 */
function get_pm_task_count(string $status = ''): int
{
    $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "fa_pm_tasks";

    if ($status !== '') {
        $sql .= " WHERE status = " . db_escape($status);
    }

    $result = db_query($sql);
    $row = db_fetch_assoc($result);

    return (int) ($row['cnt'] ?? 0);
}

/**
 * Get overdue task count
 */
function get_pm_overdue_task_count(): int
{
    $sql = "SELECT COUNT(*) as cnt FROM " . TB_PREF . "fa_pm_tasks
            WHERE end_date < CURDATE()
            AND status NOT IN ('Completed', 'Cancelled')";

    $result = db_query($sql);
    $row = db_fetch_assoc($result);

    return (int) ($row['cnt'] ?? 0);
}

/**
 * Insert new project
 */
function insert_pm_project(array $data): string
{
    $sql = "INSERT INTO " . TB_PREF . "fa_pm_projects
            (project_id, name, description, start_date, end_date, budget,
             customer_id, project_manager, priority, status)
            VALUES (
                " . db_escape($data['project_id'] ?? '') . ",
                " . db_escape($data['name']) . ",
                " . db_escape($data['description'] ?? '') . ",
                " . db_escape($data['start_date']) . ",
                " . db_escape($data['end_date'] ?? '') . ",
                " . db_escape($data['budget'] ?? 0) . ",
                " . db_escape($data['customer_id'] ?? '') . ",
                " . db_escape($data['project_manager']) . ",
                " . db_escape($data['priority'] ?? 'Medium') . ",
                " . db_escape($data['status'] ?? 'Planning') . "
            )";

    db_query($sql, "Could not insert project");

    return $data['project_id'] ?? '';
}

/**
 * Update project
 */
function update_pm_project(string $projectId, array $data): bool
{
    $sql = "UPDATE " . TB_PREF . "fa_pm_projects SET
            name = " . db_escape($data['name']) . ",
            description = " . db_escape($data['description'] ?? '') . ",
            start_date = " . db_escape($data['start_date']) . ",
            end_date = " . db_escape($data['end_date'] ?? '') . ",
            budget = " . db_escape($data['budget'] ?? 0) . ",
            customer_id = " . db_escape($data['customer_id'] ?? '') . ",
            project_manager = " . db_escape($data['project_manager']) . ",
            priority = " . db_escape($data['priority'] ?? 'Medium') . ",
            status = " . db_escape($data['status'] ?? 'Planning') . "
            WHERE project_id = " . db_escape($projectId);

    return db_query($sql, "Could not update project");
}

/**
 * Delete project
 */
function delete_pm_project(string $projectId): bool
{
    $sql = "DELETE FROM " . TB_PREF . "fa_pm_projects WHERE project_id = " . db_escape($projectId);
    return db_query($sql, "Could not delete project");
}

/**
 * Insert new task
 */
function insert_pm_task(array $data): string
{
    $sql = "INSERT INTO " . TB_PREF . "fa_pm_tasks
            (task_id, project_id, parent_task_id, name, description,
             assigned_to, start_date, end_date, estimated_hours, actual_hours,
             progress, priority, status)
            VALUES (
                " . db_escape($data['task_id'] ?? '') . ",
                " . db_escape($data['project_id']) . ",
                " . db_escape($data['parent_task_id'] ?? '') . ",
                " . db_escape($data['name']) . ",
                " . db_escape($data['description'] ?? '') . ",
                " . db_escape($data['assigned_to'] ?? '') . ",
                " . db_escape($data['start_date'] ?? '') . ",
                " . db_escape($data['end_date'] ?? '') . ",
                " . db_escape($data['estimated_hours'] ?? 0) . ",
                " . db_escape($data['actual_hours'] ?? 0) . ",
                " . db_escape($data['progress'] ?? 0) . ",
                " . db_escape($data['priority'] ?? 'Medium') . ",
                " . db_escape($data['status'] ?? 'Not Started') . "
            )";

    db_query($sql, "Could not insert task");

    return $data['task_id'] ?? '';
}

/**
 * Update task
 */
function update_pm_task(string $taskId, array $data): bool
{
    $sql = "UPDATE " . TB_PREF . "fa_pm_tasks SET
            name = " . db_escape($data['name']) . ",
            description = " . db_escape($data['description'] ?? '') . ",
            assigned_to = " . db_escape($data['assigned_to'] ?? '') . ",
            start_date = " . db_escape($data['start_date'] ?? '') . ",
            end_date = " . db_escape($data['end_date'] ?? '') . ",
            estimated_hours = " . db_escape($data['estimated_hours'] ?? 0) . ",
            actual_hours = " . db_escape($data['actual_hours'] ?? 0) . ",
            progress = " . db_escape($data['progress'] ?? 0) . ",
            priority = " . db_escape($data['priority'] ?? 'Medium') . ",
            status = " . db_escape($data['status'] ?? 'Not Started') . "
            WHERE task_id = " . db_escape($taskId);

    return db_query($sql, "Could not update task");
}

/**
 * Get project team members
 */
function get_pm_project_team(string $projectId): array
{
    $sql = "SELECT pa.*, e.employee_id, e.first_name, e.last_name, e.email, e.job_title
            FROM " . TB_PREF . "fa_pm_assignments pa
            INNER JOIN " . TB_PREF . "employees e ON pa.employee_id = e.employee_id
            WHERE pa.project_id = " . db_escape($projectId) . "
            AND (pa.end_date IS NULL OR pa.end_date >= CURDATE())
            ORDER BY e.last_name, e.first_name";

    $result = db_query($sql, "Could not retrieve project team");
    $team = [];

    while ($row = db_fetch_assoc($result)) {
        $team[] = $row;
    }

    return $team;
}

/**
 * Assign employee to project
 */
function assign_employee_to_project(array $data): bool
{
    $sql = "INSERT INTO " . TB_PREF . "fa_pm_assignments
            (project_id, employee_id, role, start_date, end_date, allocation_percentage)
            VALUES (
                " . db_escape($data['project_id']) . ",
                " . db_escape($data['employee_id']) . ",
                " . db_escape($data['role'] ?? 'Team Member') . ",
                " . db_escape($data['start_date'] ?? date('Y-m-d')) . ",
                " . db_escape($data['end_date'] ?? '') . ",
                " . db_escape($data['allocation_percentage'] ?? 100) . "
            )";

    return db_query($sql, "Could not assign employee to project");
}

/**
 * Remove employee from project
 */
function remove_employee_from_project(string $projectId, string $employeeId): bool
{
    $sql = "DELETE FROM " . TB_PREF . "fa_pm_assignments
            WHERE project_id = " . db_escape($projectId) . "
            AND employee_id = " . db_escape($employeeId);

    return db_query($sql, "Could not remove employee from project");
}

/**
 * Get recent activities
 */
function get_pm_recent_activities(int $limit = 10): array
{
    $sql = "SELECT * FROM " . TB_PREF . "fa_pm_activity_log
            ORDER BY created_at DESC
            LIMIT " . (int) $limit;

    $result = db_query($sql, "Could not retrieve activities");
    $activities = [];

    while ($row = db_fetch_assoc($result)) {
        $activities[] = $row;
    }

    return $activities;
}