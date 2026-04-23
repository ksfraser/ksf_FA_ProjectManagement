-- FA_PM Module SQL Schema
-- Install: Creates tables for Project Management module
-- Prefix: fa_pm_

-- Projects Table
CREATE TABLE IF NOT EXISTS `@TB_PREF@fa_pm_projects` (
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
    `project_type_id` INT(11) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`project_id`),
    KEY `idx_status` (`status`),
    KEY `idx_manager` (`project_manager`),
    KEY `idx_customer` (`customer_id`),
    KEY `idx_priority` (`priority`),
    KEY `idx_start_date` (`start_date`),
    CONSTRAINT `fk_pm_customer` FOREIGN KEY (`customer_id`) REFERENCES `@TB_PREF@debtors_master` (`debtor_no`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tasks Table
CREATE TABLE IF NOT EXISTS `@TB_PREF@fa_pm_tasks` (
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
    KEY `idx_status` (`status`),
    KEY `idx_priority` (`priority`),
    CONSTRAINT `fk_task_project` FOREIGN KEY (`project_id`) REFERENCES `@TB_PREF@fa_pm_projects` (`project_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Assignments Table
CREATE TABLE IF NOT EXISTS `@TB_PREF@fa_pm_assignments` (
    `project_id` VARCHAR(20) NOT NULL,
    `employee_id` VARCHAR(100) NOT NULL,
    `role` VARCHAR(50) DEFAULT 'Team Member',
    `start_date` DATE NOT NULL,
    `end_date` DATE DEFAULT NULL,
    `allocation_percentage` DECIMAL(5,2) DEFAULT 100.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`project_id`, `employee_id`),
    KEY `idx_employee` (`employee_id`),
    KEY `idx_end_date` (`end_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Project Types Table
CREATE TABLE IF NOT EXISTS `@TB_PREF@fa_pm_project_types` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) DEFAULT NULL,
    `inactive` TINYINT(1) DEFAULT 0,
    `sort_order` INT(11) DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `idx_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity Log Table
CREATE TABLE IF NOT EXISTS `@TB_PREF@fa_pm_activity_log` (
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
    KEY `idx_entity` (`entity_type`, `entity_id`),
    KEY `idx_user` (`user_id`),
    KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Project Types
INSERT INTO `@TB_PREF@fa_pm_project_types` (`name`, `description`, `sort_order`) VALUES
('Software Development', 'Software development projects', 1),
('Infrastructure', 'Infrastructure and DevOps projects', 2),
('Consulting', 'Consulting and professional services', 3),
('Research', 'Research and development projects', 4),
('Marketing', 'Marketing and campaign projects', 5),
('Event', 'Event planning and execution', 6);