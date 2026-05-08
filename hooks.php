<?php
/**
 * FA_PM Module Hooks for FrontAccounting
 * Project Management with OKR integration
 * Links to Performance module for goal tracking
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
                $app->add_lapp_function(3, _("Gantt Chart"),
                    $path_to_root."/modules/".$this->module_name."/gantt.php", 'SA_PMVIEW', MENU_ENTRY);
                $app->add_rapp_function(4, _("Project Reports"),
                    $path_to_root."/modules/".$this->module_name."/reports.php", 'SA_PMVIEW', MENU_REPORT);
                $app->add_rapp_function(5, _("Utilization"),
                    $path_to_root."/modules/".$this->module_name."/utilization.php", 'SA_PMVIEW', MENU_REPORT);
                break;
            case 'HR':
                $app->add_rapp_function(0, _("Linked Goals"),
                    $path_to_root."/modules/".$this->module_name."/project_goals.php", 'SA_PMMANAGE', MENU_ENTRY);
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
        // FA's update_databases handles multiple SQL files automatically
        $updates = array(
            // Core PM tables
            'sql/fa_pm_projects.sql' => array($this->module_name),
            'sql/fa_pm_tasks.sql' => array($this->module_name),
            'sql/fa_pm_assignments.sql' => array($this->module_name),
            'sql/fa_pm_project_types.sql' => array($this->module_name),
            'sql/fa_pm_activity_log.sql' => array($this->module_name),
            'sql/fa_pm_files.sql' => array($this->module_name),
            // Progress tracking (OpenProject-style)
            'sql/fa_pm_task_progress.sql' => array($this->module_name),
            // PM versions/quarters (for OKR)
            'sql/fa_pm_versions.sql' => array($this->module_name)
        );
        return $this->update_databases($company, $updates, $check_only);
    }

    function db_prevoid($trans_type, $trans_no) {
        // Handle voiding if PM tracks financial transactions
    }
}
?>
