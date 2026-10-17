<?php

declare(strict_types=1);

namespace ksfraser\FrontAccounting\ProjectManagement\Dictionary;

use ksfraser\CommonDb\Contract\DbConnectionInterface;
use ksfraser\CommonDb\Adapter\FaDbAdapter;
use ksfraser\CommonDb\Dictionary\TableDefinition;

/**
 * PM data dictionary — the logical schema for every PM table.
 *
 * The DAO layer (repositories) codes against this dictionary and the
 * transport-agnostic DbConnectionInterface, so the same SQL works inside FA
 * (FaDbAdapter → native db_* calls) and standalone (PdoDbAdapter → prepared
 * statements). This is the PM module's side of the shared data-dictionary /
 * query-builder direction: an interface defines the SQL commands, a
 * translation layer maps them per transport, and the correct implementation
 * is DI'd.
 *
 * install.sql remains the source of truth applied by FA at activation; these
 * definitions mirror it for dictionary-driven CRUD (insertSql/updateSql/
 * deleteSql) and are available for CREATE TABLE in standalone/CLI use.
 *
 * PHP 7.3 compatible.
 *
 * @package ksf_FA_ProjectManagement
 * @since   1.0.0
 *
 * @UML Note: AGENTS.md §ksf_common_db (data-dictionary + query-builder)
 * @BABOK Related: BR-012 (Project Management)
 */
class Schema
{
    /** Logical (unprefixed) table names as the adapter auto-prefixes them. */

    const T_PROJECTS    = 'fa_pm_projects';
    const T_TASKS       = 'fa_pm_tasks';
    const T_TASK_DEPENDENCIES = 'fa_pm_task_dependencies';
    const T_ASSIGNMENTS = 'fa_pm_assignments';
    const T_PROJECT_TYPES = 'fa_pm_project_types';
    const T_ACTIVITY    = 'fa_pm_activity_log';
    const T_FILES       = 'fa_pm_files';
    const T_PROGRESS    = 'fa_pm_task_progress';
    const T_SALES_ORDERS = 'fa_pm_project_sales_orders';
    const T_REVENUE     = 'fa_pm_project_revenue';

    /**
     * The FA runtime adapter (native db_* only). DI'd into repositories by
     * default; tests/CLI inject PdoDbAdapter or a stub interface instead.
     *
     * @return DbConnectionInterface
     */
    public static function adapter(): DbConnectionInterface
    {
        return new FaDbAdapter(defined('TB_PREF') ? TB_PREF : '');
    }

    /**
     * Data dictionary definition for the projects table.
     *
     * @return TableDefinition
     */
    public static function projects(): TableDefinition
    {
        return (new TableDefinition(self::T_PROJECTS, 'project_id'))
            ->column('project_id', 'varchar(20)', 'NOT NULL')
            ->column('name', 'varchar(100)', 'NOT NULL')
            ->column('description', 'text')
            ->column('start_date', 'date', 'NOT NULL')
            ->column('end_date', 'date')
            ->column('budget', 'decimal(15,2)', '', false, 0.00)
            ->column('customer_id', 'int(11)')
            ->column('project_manager', 'varchar(100)', 'NOT NULL')
            ->column('priority', 'varchar(20)', '', false, "'Medium'")
            ->column('status', 'varchar(30)', '', false, "'Planning'")
            ->column('project_type_id', 'int(11)')
            ->column('created_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->column('updated_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_status', 'index', 'status')
            ->index('idx_manager', 'index', 'project_manager')
            ->index('idx_customer', 'index', 'customer_id')
            ->index('idx_start_date', 'index', 'start_date');
    }

    /**
     * Data dictionary definition for the tasks table.
     *
     * @return TableDefinition
     */
    public static function tasks(): TableDefinition
    {
        return (new TableDefinition(self::T_TASKS, 'task_id'))
            ->column('task_id', 'varchar(20)', 'NOT NULL')
            ->column('project_id', 'varchar(20)', 'NOT NULL')
            ->column('parent_task_id', 'varchar(20)', '', false, "''")
            ->column('name', 'varchar(100)', 'NOT NULL')
            ->column('description', 'text')
            ->column('assigned_to', 'varchar(100)')
            ->column('start_date', 'date')
            ->column('end_date', 'date')
            ->column('estimated_hours', 'decimal(10,2)', '', false, 0.00)
            ->column('actual_hours', 'decimal(10,2)', '', false, 0.00)
            ->column('progress', 'decimal(5,2)', '', false, 0.00)
            ->column('priority', 'varchar(20)', '', false, "'Medium'")
            ->column('status', 'varchar(30)', '', false, "'Not Started'")
            ->column('is_milestone', 'tinyint(1)', '', false, 0)
            ->column('constraint_type', 'varchar(30)')
            ->column('constraint_date', 'date')
            ->column('es', 'date')
            ->column('ef', 'date')
            ->column('ls', 'date')
            ->column('lf', 'date')
            ->column('slack', 'decimal(10,2)', '', false, 0.00)
            ->column('is_critical', 'tinyint(1)', '', false, 0)
            ->column('created_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->column('updated_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_project', 'index', 'project_id')
            ->index('idx_parent', 'index', 'parent_task_id')
            ->index('idx_assignee', 'index', 'assigned_to')
            ->index('idx_status', 'index', 'status')
            ->index('idx_critical', 'index', 'is_critical')
            ->index('idx_milestone', 'index', 'is_milestone');
    }

    /**
     * Data dictionary definition for the task-dependency table (CPM edges).
     *
     * Each row is a predecessor→task precedence edge carrying a dependency
     * type (FS/SS/FF/SF) and an optional lag. The CpmEngine consumes these as
     * weighted adjacency and scheduling services persist the computed
     * ES/EF/LS/LF/slack/critical back onto the tasks() dictionary.
     *
     * @return TableDefinition
     */
    public static function taskDependencies(): TableDefinition
    {
        return (new TableDefinition(self::T_TASK_DEPENDENCIES, 'dependency_id'))
            ->column('dependency_id', 'int(11)', 'NOT NULL', true)
            ->column('task_id', 'varchar(20)', 'NOT NULL')
            ->column('predecessor_id', 'varchar(20)', 'NOT NULL')
            ->column('dependency_type', 'varchar(10)', 'NOT NULL', false, "'FS'")
            ->column('lag', 'decimal(10,2)', '', false, 0.00)
            ->column('created_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_task', 'index', 'task_id')
            ->index('idx_pred', 'index', 'predecessor_id')
            ->index('idx_unique', 'unique', 'task_id, predecessor_id');
    }

    /**
     * Data dictionary definition for the team-assignment table.
     *
     * @return TableDefinition
     */
    public static function assignments(): TableDefinition
    {
        return (new TableDefinition(self::T_ASSIGNMENTS, 'project_id'))
            ->column('project_id', 'varchar(20)', 'NOT NULL')
            ->column('employee_id', 'varchar(100)', 'NOT NULL')
            ->column('role', 'varchar(50)', '', false, "'Team Member'")
            ->column('start_date', 'date', 'NOT NULL')
            ->column('end_date', 'date')
            ->column('allocation_percentage', 'decimal(5,2)', '', false, 100.00)
            ->column('created_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_employee', 'index', 'employee_id')
            ->index('idx_end_date', 'index', 'end_date');
    }

    /**
     * Data dictionary definition for the project-types reference table.
     *
     * @return TableDefinition
     */
    public static function projectTypes(): TableDefinition
    {
        return (new TableDefinition(self::T_PROJECT_TYPES, 'id'))
            ->column('id', 'int(11)', 'NOT NULL', true)
            ->column('name', 'varchar(50)', 'NOT NULL')
            ->column('description', 'varchar(255)')
            ->column('inactive', 'tinyint(1)', '', false, 0)
            ->column('sort_order', 'int(11)', '', false, 0)
            ->index('idx_sort', 'index', 'sort_order');
    }

    /**
     * Data dictionary definition for the activity-log table.
     *
     * @return TableDefinition
     */
    public static function activityLog(): TableDefinition
    {
        return (new TableDefinition(self::T_ACTIVITY, 'id'))
            ->column('id', 'int(11)', 'NOT NULL', true)
            ->column('activity_type', 'varchar(30)', 'NOT NULL')
            ->column('entity_type', 'varchar(30)', 'NOT NULL')
            ->column('entity_id', 'varchar(20)', 'NOT NULL')
            ->column('user_id', 'varchar(100)')
            ->column('action', 'varchar(50)', 'NOT NULL')
            ->column('details', 'text')
            ->column('old_values', 'text')
            ->column('new_values', 'text')
            ->column('ip_address', 'varchar(45)')
            ->column('created_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_entity', 'index', 'entity_type, entity_id')
            ->index('idx_user', 'index', 'user_id')
            ->index('idx_created', 'index', 'created_at');
    }

    /**
     * Data dictionary definition for the file-attachment table.
     *
     * @return TableDefinition
     */
    public static function files(): TableDefinition
    {
        return (new TableDefinition(self::T_FILES, 'id'))
            ->column('id', 'int(11)', 'NOT NULL', true)
            ->column('entity_type', 'varchar(30)', 'NOT NULL')
            ->column('entity_id', 'varchar(20)', 'NOT NULL')
            ->column('file_name', 'varchar(255)', 'NOT NULL')
            ->column('original_name', 'varchar(255)', 'NOT NULL')
            ->column('mime_type', 'varchar(100)', '', false, "'application/octet-stream'")
            ->column('size', 'int(11)', '', false, 0)
            ->column('storage_type', 'varchar(20)', '', false, "'local'")
            ->column('storage_path', 'varchar(500)', '', false, "''")
            ->column('uploaded_by', 'varchar(100)')
            ->column('uploaded_at', 'datetime', '', false, 'CURRENT_TIMESTAMP')
            ->column('description', 'text')
            ->column('inactive', 'tinyint(1)', '', false, 0)
            ->index('idx_entity', 'index', 'entity_type, entity_id')
            ->index('idx_uploaded_by', 'index', 'uploaded_by');
    }

    /**
     * Data dictionary definition for OpenProject-style task progress.
     *
     * @return TableDefinition
     */
    public static function taskProgress(): TableDefinition
    {
        return (new TableDefinition(self::T_PROGRESS, 'progress_id'))
            ->column('progress_id', 'int(11)', 'NOT NULL', true)
            ->column('task_id', 'varchar(20)', 'NOT NULL')
            ->column('progress_mode', 'varchar(20)', '', false, "'work_based'")
            ->column('work_hours', 'decimal(10,2)', '', false, 0)
            ->column('remaining_hours', 'decimal(10,2)', '', false, 0)
            ->column('percent_complete', 'decimal(5,2)', '', false, 0)
            ->column('status', 'varchar(30)')
            ->column('baseline_hours', 'decimal(10,2)', '', false, 0)
            ->column('updated_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_task', 'unique', 'task_id');
    }

    /**
     * Data dictionary definition for project-to-FA-order links.
     *
     * @return TableDefinition
     */
    public static function projectSalesOrders(): TableDefinition
    {
        return (new TableDefinition(self::T_SALES_ORDERS, 'id'))
            ->column('id', 'int(11)', 'NOT NULL', true)
            ->column('project_id', 'varchar(20)', 'NOT NULL')
            ->column('fa_order_no', 'int(11)', 'NOT NULL')
            ->column('fa_trans_type', 'int(11)', '', false, 10)
            ->column('source', 'varchar(32)', '', false, "'all'")
            ->column('source_order_id', 'varchar(64)')
            ->column('linked_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_order', 'unique', 'fa_order_no, fa_trans_type')
            ->index('idx_project', 'index', 'project_id')
            ->index('idx_source', 'index', 'source');
    }

    /**
     * Data dictionary definition for project revenue rows.
     *
     * @return TableDefinition
     */
    public static function projectRevenue(): TableDefinition
    {
        return (new TableDefinition(self::T_REVENUE, 'revenue_id'))
            ->column('revenue_id', 'int(11)', 'NOT NULL', true)
            ->column('project_id', 'varchar(20)', 'NOT NULL')
            ->column('fa_order_no', 'int(11)', 'NOT NULL')
            ->column('fa_trans_type', 'int(11)', '', false, 10)
            ->column('source', 'varchar(32)', '', false, "'all'")
            ->column('source_order_id', 'varchar(64)')
            ->column('order_total', 'decimal(15,2)', '', false, 0)
            ->column('revenue_amount', 'decimal(15,2)', '', false, 0)
            ->column('order_date', 'date')
            ->column('created_at', 'timestamp', '', false, 'CURRENT_TIMESTAMP')
            ->index('idx_revenue_order', 'unique', 'fa_order_no, fa_trans_type')
            ->index('idx_project', 'index', 'project_id')
            ->index('idx_source', 'index', 'source');
    }

    /**
     * All dictionary CREATE TABLE statements, keyed by logical table name.
     *
     * @return array<string, string>
     */
    public static function createSqls(): array
    {
        $defs = [
            self::T_PROJECTS,
            self::T_TASKS,
            self::T_ASSIGNMENTS,
            self::T_PROJECT_TYPES,
            self::T_ACTIVITY,
            self::T_PROGRESS,
            self::T_SALES_ORDERS,
            self::T_REVENUE,
            self::T_FILES,
        ];

        $out = [];
        foreach ($defs as $name) {
            $out[$name] = self::definitionFor($name)->createSql();
        }

        return $out;
    }

    /**
     * Look up the definition for a logical table name.
     *
     * @param string $table Logical table name (one of the T_* constants)
     * @return TableDefinition
     */
    public static function definitionFor(string $table): TableDefinition
    {
        $map = [
            self::T_PROJECTS       => 'projects',
            self::T_TASKS          => 'tasks',
            self::T_ASSIGNMENTS    => 'assignments',
            self::T_PROJECT_TYPES  => 'projectTypes',
            self::T_ACTIVITY       => 'activityLog',
            self::T_FILES          => 'files',
            self::T_PROGRESS       => 'taskProgress',
            self::T_SALES_ORDERS   => 'projectSalesOrders',
            self::T_REVENUE        => 'projectRevenue',
        ];

        $method = $map[$table] ?? null;
        return $method !== null
            ? self::$method()
            : new TableDefinition($table);
    }
}