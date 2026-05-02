<?php
/**
 * FA_PM Module Hooks for FrontAccounting
 */

define('SS_PM', 131 << 8);

class hooks_fa_projectmanagement extends hooks {
    var $module_name = 'fa_projectmanagement';

    function install_options($app) {
        global $path_to_root;

        switch($app->id) {
            case 'Projects':
                $app->add_lapp_function(0, _("Projects"),
                    $path_to_root."/modules/".$this->module_name."/projects.php", 'SA_PMVIEW', MENU_ENTRY);
                $app->add_lapp_function(1, _("Tasks"),
                    $path_to_root."/modules/".$this->module_name."/tasks.php", 'SA_PMCREATE', MENU_ENTRY);
                $app->add_lapp_function(2, _("Assignments"),
                    $path_to_root."/modules/".$this->module_name."/assignments.php", 'SA_PMMANAGE', MENU_ENTRY);
                $app->add_rapp_function(3, _("Project Reports"),
                    $path_to_root."/modules/".$this->module_name."/reports.php", 'SA_PMVIEW', MENU_REPORT);
                break;
        }
    }

    function install_access() {
        $security_sections[SS_PM] = _("Project Management");
        $security_areas['SA_PMVIEW'] = array(SS_PM | 1, _("View Projects"));
        $security_areas['SA_PMCREATE'] = array(SS_PM | 2, _("Create Projects"));
        $security_areas['SA_PMMANAGE'] = array(SS_PM | 3, _("Manage Projects"));
        return array($security_areas, $security_sections);
    }

    function activate_extension($company, $check_only=true) {
        $updates = array('sql/update.sql' => array($this->module_name));
        $ok = $this->update_databases($company, $updates, $check_only);
        if ($check_only || !$ok) {
            return $ok;
        }
        $this->ensure_pm_schema();
        return $ok;
    }

    private function table_exists($table) {
        $sql = "SHOW TABLES LIKE " . db_escape($table);
        $res = db_query($sql, 'Failed checking table existence');
        return db_num_rows($res) > 0;
    }

    private function ensure_pm_schema() {
        $tables = array(
            TB_PREF . "fa_pm_projects" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_pm_projects` (
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

            TB_PREF . "fa_pm_tasks" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_pm_tasks` (
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

            TB_PREF . "fa_pm_assignments" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_pm_assignments` (
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

            TB_PREF . "fa_pm_project_types" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_pm_project_types` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `name` VARCHAR(50) NOT NULL,
                    `description` VARCHAR(255) DEFAULT NULL,
                    `inactive` TINYINT(1) DEFAULT 0,
                    `sort_order` INT(11) DEFAULT 0,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

            TB_PREF . "fa_pm_activity_log" => "
                CREATE TABLE IF NOT EXISTS `" . TB_PREF . "fa_pm_activity_log` (
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
        );

        foreach ($tables as $table_name => $sql) {
            db_query($sql, "Could not create PM table: $table_name");
        }

        $this->insert_pm_initial_data();
    }

    private function insert_pm_initial_data() {
        $project_types = array(
            array('name' => 'Software Development', 'description' => 'Software development projects', 'sort_order' => 1),
            array('name' => 'Infrastructure', 'description' => 'Infrastructure and DevOps projects', 'sort_order' => 2),
            array('name' => 'Consulting', 'description' => 'Consulting and professional services', 'sort_order' => 3),
            array('name' => 'Research', 'description' => 'Research and development projects', 'sort_order' => 4),
        );

        foreach ($project_types as $type) {
            $sql = "INSERT IGNORE INTO " . TB_PREF . "fa_pm_project_types
                (name, description, sort_order) VALUES
                (" . db_escape($type['name']) . ", " . db_escape($type['description']) . ", " . db_escape($type['sort_order']) . ")";
            db_query($sql, "Could not insert project type");
        }
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if PM tracks financial transactions
    }
}
?>
