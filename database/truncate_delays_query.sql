SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tbl_delay_mitigations;      -- mitigations per delay
TRUNCATE TABLE tbl_delay_attachments;      -- file metadata
TRUNCATE TABLE tbl_delay_financial_impacts; -- financial rows
TRUNCATE TABLE tbl_delay_registers;        -- main delay logs
SET FOREIGN_KEY_CHECKS = 1;


UPDATE tbl_project_departments
SET
    department_status = CASE
        WHEN actual_start_date IS NOT NULL THEN 'in_progress'
        ELSE 'start'
    END,
    delay_days = 0,
    updated_on = NOW()
WHERE is_delete = 0
  AND department_status = 'delay';
  
  UPDATE tbl_projects p
SET
    p.project_status = CASE
        WHEN NOT EXISTS (
            SELECT 1 FROM tbl_project_departments pd
            WHERE pd.project_id = p.id AND pd.is_delete = 0
              AND pd.department_status <> 'completed'
        ) THEN 'completed'
        WHEN EXISTS (
            SELECT 1 FROM tbl_project_departments pd
            WHERE pd.project_id = p.id AND pd.is_delete = 0
              AND pd.department_status = 'delay'
        ) THEN 'delayed'
        ELSE 'active'
    END,
    p.total_delay_cost = 0,
    p.updated_on = NOW()
WHERE p.is_delete = 0
  AND p.project_status <> 'on_hold';
  
 DELETE FROM tbl_user_in_app_notifications
WHERE notification_type = 'delay_logged'
   OR entity_type = 'delay_register';