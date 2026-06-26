-- Manual fallback when artisan migrate is unavailable (run against db_pdts).
-- Adds optional link from delay register to project department task.

ALTER TABLE `tbl_delay_registers`
    ADD COLUMN `project_department_task_id` INT NULL
        COMMENT 'tbl_project_department_tasks.id — optional impacted task'
        AFTER `project_department_id`,
    ADD INDEX `tbl_delay_registers_project_department_task_id_index` (`project_department_task_id`);
