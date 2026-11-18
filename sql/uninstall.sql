-- FA_PM Module Uninstall SQL
-- Removes all Project Management tables and data
--
-- `0_` is literal: FA's db_import() rewrites it to TB_PREF. It does NOT
-- substitute @TB_PREF@, so the placeholder form never worked.
--
-- Drop tables in reverse order of dependencies.
DROP TABLE IF EXISTS `0_ksf_projectmanagement_project_revenue`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_project_sales_orders`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_task_progress`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_files`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_activity_log`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_task_dependencies`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_assignments`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_tasks`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_projects`;
DROP TABLE IF EXISTS `0_ksf_projectmanagement_project_types`;

-- Note: Foreign key constraint to debtors_master will be automatically handled
-- The customer_id column in 0_ksf_projectmanagement_projects references
-- debtors_master.debtor_no, but ON DELETE SET NULL means no data loss in the
-- main table.
