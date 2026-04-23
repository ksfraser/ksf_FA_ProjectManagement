-- FA_PM Module Uninstall SQL
-- Removes all Project Management tables and data

-- Drop tables in reverse order of dependencies
DROP TABLE IF EXISTS `@TB_PREF@fa_pm_activity_log`;
DROP TABLE IF EXISTS `@TB_PREF@fa_pm_assignments`;
DROP TABLE IF EXISTS `@TB_PREF@fa_pm_tasks`;
DROP TABLE IF EXISTS `@TB_PREF@fa_pm_projects`;
DROP TABLE IF EXISTS `@TB_PREF@fa_pm_project_types`;

-- Note: Foreign key constraint to debtors_master will be automatically handled
-- The customer_id column in fa_pm_projects references debtors_master.debtor_no
-- but ON DELETE SET NULL means no data loss in the main table