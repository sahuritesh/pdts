-- Run once in phpMyAdmin (database: db_pdts) if artisan migrate is unavailable.
-- Fixes wizard error: "Task module is not available"

CREATE TABLE IF NOT EXISTS `tbl_project_department_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int NOT NULL COMMENT 'tbl_projects.id',
  `project_department_id` int NOT NULL COMMENT 'tbl_project_departments.id — parent dept instance',
  `parent_task_id` int DEFAULT NULL COMMENT 'tbl_project_department_tasks.id — sub-task parent',
  `sort_order` smallint unsigned NOT NULL DEFAULT 0,
  `task_name` varchar(255) NOT NULL,
  `task_kind` varchar(30) NOT NULL DEFAULT 'standard' COMMENT 'standard, linked_department',
  `linked_department_id` int DEFAULT NULL COMMENT 'tbl_departments.id',
  `linked_project_department_id` int DEFAULT NULL COMMENT 'tbl_project_departments.id — drill-down target',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `actual_start_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `task_status` varchar(30) NOT NULL DEFAULT 'not_started' COMMENT 'not_started, in_progress, completed, on_hold',
  `owner_user_id` int DEFAULT NULL COMMENT 'tbl_user.id',
  `owner_name` varchar(255) DEFAULT NULL,
  `remarks` text DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_project_department_tasks_project_id_index` (`project_id`),
  KEY `tbl_project_department_tasks_project_department_id_index` (`project_department_id`),
  KEY `tbl_project_department_tasks_parent_task_id_index` (`parent_task_id`),
  KEY `tbl_project_department_tasks_linked_department_id_index` (`linked_department_id`),
  KEY `tbl_project_department_tasks_linked_project_department_id_index` (`linked_project_department_id`),
  KEY `tbl_project_department_tasks_task_status_index` (`task_status`),
  KEY `tbl_project_department_tasks_sort_order_index` (`sort_order`),
  KEY `tbl_project_department_tasks_is_delete_index` (`is_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_19_100000_create_tbl_project_department_tasks', IFNULL(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations` WHERE `migration` = '2026_06_19_100000_create_tbl_project_department_tasks'
);
