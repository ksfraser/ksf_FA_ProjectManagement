<?php
/**
 * FA_PM Module Hooks for FrontAccounting
 *
 * Handles module installation, activation, and database setup
 *
 * @package FA_PM
 * @version 1.0.0
 * @author KSFII Development Team
 */

// Module metadata
$module_name = 'FA_PM';
$module_version = '1.0.0';
$module_description = 'Project Management for FrontAccounting (Powered by ksf-project-management)';
$module_author = 'KSFII Development Team';
$module_category = 'Project';

/**
 * Install hook - called when module is installed
 */
function fa_pm_install(): bool
{
    global $db;

    if (!fa_pm_create_tables()) {
        return false;
    }

    if (!fa_pm_insert_initial_data()) {
        return false;
    }

    return true;
}

/**
 * Activate hook - called when module is activated
 */
function fa_pm_activate(): bool
{
    add_hook('project_delete', 'fa_pm_project_delete');
    add_hook('project_update', 'fa_pm_project_update');
    add_hook('task_update', 'fa_pm_task_update');

    fa_pm_enable_features();

    return true;
}

/**
 * Deactivate hook - called when module is deactivated
 */
function fa_pm_deactivate(): bool
{
    return true;
}

/**
 * Uninstall hook - called when module is uninstalled
 */
function fa_pm_uninstall(): bool
{
    return true;
}

/**
 * Create database tables for FA_PM module
 */
function fa_pm_create_tables(): bool
{
    global $db;

    $table_prefix = TB_PREF;

    $sql_statements = [
        "CREATE TABLE IF NOT EXISTS `{$table_prefix}fa_pm_projects` (
            `project_id` VARCHAR(20) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `start_date` DATE NOT NULL,
            `end_date` DATE DEFAULT NULL,
            `budget` DECIMAL(15,2) DEFAULT 0.00,
            `customer_id` VARCHAR(20) DEFAULT NULL,
            `project_manager` VARCHAR(100) NOT NULL,
            `priority` VARCHAR(20) DEFAULT 'Medium',
            `status` VARCHAR(30) DEFAULT 'Planning',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`project_id`),
            KEY `idx_status` (`status`),
            KEY `idx_manager` (`project_manager`),
            KEY `idx_customer` (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `{$table_prefix}fa_pm_tasks` (
            `task_id` VARCHAR(20) NOT NULL,
            `project_id` VARCHAR(20) NOT NULL,
            `parent_task_id` VARCHAR(20) DEFAULT '',
            `name` VARCHAR(100) NOT NULL,
            `description` TEXT,
            `assigned_to` VARCHAR(100) DEFAULT NULL,
            `start_date` DATE DEFAULT NULL,
            `end_date` DATE DEFAULT NULL,
            `estimated_hours` DECIMAL(10,2) DEFAULT 0.00,
            `actual_hours` DECIMAL(10,2) DEFAULT 0.00,
            `progress` DECIMAL(5,2) DEFAULT 0.00,
            `priority` VARCHAR(20) DEFAULT 'Medium',
            `status` VARCHAR(30) DEFAULT 'Not Started',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`task_id`),
            KEY `idx_project` (`project_id`),
            KEY `idx_parent` (`parent_task_id`),
            KEY `idx_assignee` (`assigned_to`),
            KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `{$table_prefix}fa_pm_assignments` (
            `project_id` VARCHAR(20) NOT NULL,
            `employee_id` VARCHAR(100) NOT NULL,
            `role` VARCHAR(50) DEFAULT 'Team Member',
            `start_date` DATE NOT NULL,
            `end_date` DATE DEFAULT NULL,
            `allocation_percentage` DECIMAL(5,2) DEFAULT 100.00,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`project_id`, `employee_id`),
            KEY `idx_employee` (`employee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `{$table_prefix}fa_pm_project_types` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `name` VARCHAR(50) NOT NULL,
            `description` VARCHAR(255) DEFAULT NULL,
            `inactive` TINYINT(1) DEFAULT 0,
            `sort_order` INT(11) DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `{$table_prefix}fa_pm_activity_log` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `activity_type` VARCHAR(30) NOT NULL,
            `entity_type` VARCHAR(30) NOT NULL,
            `entity_id` VARCHAR(20) NOT NULL,
            `user_id` VARCHAR(100) DEFAULT NULL,
            `action` VARCHAR(50) NOT NULL,
            `details` TEXT,
            `old_values` TEXT,
            `new_values` TEXT,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `idx_entity` (`entity_type`, `entity_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($sql_statements as $sql) {
        if (!db_query($sql, "Could not create FA_PM table")) {
            return false;
        }
    }

    return true;
}

/**
 * Insert initial data for FA_PM module
 */
function fa_pm_insert_initial_data(): bool
{
    $project_types = [
        ['name' => 'Software Development', 'description' => 'Software development projects', 'sort_order' => 1],
        ['name' => 'Infrastructure', 'description' => 'Infrastructure and DevOps projects', 'sort_order' => 2],
        ['name' => 'Consulting', 'description' => 'Consulting and professional services', 'sort_order' => 3],
        ['name' => 'Research', 'description' => 'Research and development projects', 'sort_order' => 4],
    ];

    foreach ($project_types as $type) {
        $sql = "INSERT IGNORE INTO " . TB_PREF . "fa_pm_project_types
            (name, description, sort_order) VALUES
            (" . db_escape($type['name']) . ", " . db_escape($type['description']) . ", " . db_escape($type['sort_order']) . ")";
        db_query($sql, "Could not insert project type");
    }

    return true;
}

function fa_pm_enable_features(): void
{
    return;
}

function fa_pm_project_delete(string $projectId): bool
{
    $sql = "UPDATE " . TB_PREF . "fa_pm_projects SET status = 'Deleted' WHERE project_id = " . db_escape($projectId);
    db_query($sql, "Could not soft delete project");
    return true;
}

function fa_pm_project_update(string $projectId, array $data): bool
{
    return true;
}

function fa_pm_task_update(string $taskId, array $data): bool
{
    return true;
}

function fa_pm_get_module_info(): array
{
    return [
        'name' => 'FA_PM',
        'version' => $module_version,
        'description' => $module_description,
        'author' => $module_author,
        'category' => $module_category,
        'depends' => ['ksfraser/ksf-project-management'],
    ];
}