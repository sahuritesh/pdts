/*
SQLyog Community v13.3.1 (64 bit)
MySQL - 10.4.32-MariaDB : Database - db_pdts
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `migrations` */

DROP TABLE IF EXISTS `migrations`;

CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `migrations` */

insert  into `migrations`(`id`,`migration`,`batch`) values 
(1,'2019_12_14_000001_create_personal_access_tokens_table',1),
(2,'2025_01_02_100000_make_user_id_nullable_in_tbl_user_device_tokens',1),
(3,'2025_01_02_110000_create_tbl_notification_logs',1),
(4,'2025_01_15_100000_create_tbl_user_device_tokens',1),
(5,'2025_02_17_100000_create_tbl_user',1),
(6,'2025_02_17_100100_create_tbl_roles',1),
(7,'2026_02_04_172609_deactivate_all_device_tokens_for_firebase_project_migration',1),
(8,'2026_04_24_100000_remove_unused_profile_columns_from_tbl_user',2),
(9,'2026_06_08_100600_remove_mobile_app_columns_from_tbl_user',2),
(10,'2026_06_09_100000_create_tbl_delay_categories',2),
(11,'2026_06_09_100100_create_tbl_projects',2),
(12,'2026_06_09_100200_create_tbl_delay_registers',2),
(13,'2026_06_09_100300_create_tbl_delay_severity_rules',2),
(14,'2026_06_09_100400_create_tbl_delay_mitigations',2),
(15,'2026_06_09_100500_create_tbl_delay_financial_impacts',2),
(16,'2026_06_09_100600_create_tbl_delay_attachments',2),
(17,'2026_06_09_100700_create_tbl_ews_config_tables',2),
(18,'2026_06_09_100800_create_tbl_ews_potential_delay_alerts',2),
(19,'2026_06_09_100900_create_tbl_renovation_projects',2),
(20,'2026_06_09_101000_create_tbl_renovation_tasks',2),
(21,'2026_06_09_101100_create_tbl_renovation_operational_impacts',3),
(22,'2026_06_09_101200_create_tbl_renovation_task_dependencies',3),
(23,'2026_06_09_101300_create_tbl_renovation_risk_assessments',3),
(24,'2026_06_09_101400_create_tbl_renovation_procurements',3),
(25,'2026_06_09_101500_create_tbl_renovation_approvals',3),
(26,'2026_06_09_101600_create_tbl_renovation_change_orders',3),
(27,'2026_06_09_101700_create_tbl_renovation_cost_tracking',3),
(28,'2026_06_09_101800_create_tbl_renovation_daily_delay_logs',3),
(29,'2026_06_09_101900_create_tbl_audit_trails',3),
(30,'2026_06_09_102000_ensure_no_foreign_keys_on_pdts_tables',3),
(31,'2026_06_09_103000_align_schema_with_excel_framework',4),
(32,'2026_06_09_104000_create_tbl_zones_and_add_zone_id_to_projects',5);

/*Table structure for table `personal_access_tokens` */

DROP TABLE IF EXISTS `personal_access_tokens`;

CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `personal_access_tokens` */

/*Table structure for table `tbl_audit_trails` */

DROP TABLE IF EXISTS `tbl_audit_trails`;

CREATE TABLE `tbl_audit_trails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(100) NOT NULL COMMENT 'e.g. delay_register, renovation_task',
  `entity_id` bigint(20) unsigned NOT NULL,
  `action` varchar(50) NOT NULL COMMENT 'create, update, delete, status_change',
  `old_values` text DEFAULT NULL,
  `new_values` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL COMMENT 'tbl_user.id',
  `created_on` datetime DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL COMMENT 'tbl_user.id',
  `modified_on` datetime DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_audit_trails_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `tbl_audit_trails_created_by_index` (`created_by`),
  KEY `tbl_audit_trails_created_on_index` (`created_on`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_audit_trails` */

insert  into `tbl_audit_trails`(`id`,`entity_type`,`entity_id`,`action`,`old_values`,`new_values`,`created_by`,`created_on`,`modified_by`,`modified_on`,`ip_address`,`user_agent`) values 
(1,'project',2,'update','{\"id\":2,\"project_code\":\"AH-Gurugram - CONST-02\",\"project_name\":\"Apollo Gurugram \\u2014 MRI Room Fit-out\",\"project_type_id\":1,\"project_type_label\":\"Green Field\",\"project_scope\":null,\"location\":\"Gurugram\",\"hospital_name\":\"Apollo Hospitals, Gurugram\",\"contractor_name\":null,\"zone_department\":\"MRI Room\",\"area_facility\":\"MRI Room\",\"responsible_user_id\":null,\"responsibility_name\":\"Mr.Operations\",\"project_spoc_name\":\"Mr.Operations\",\"planned_start_date\":\"2026-06-25\",\"planned_completion_date\":\"2026-06-30\",\"actual_completion_date\":null,\"target_revised_completion_date\":null,\"project_status\":\"delayed\",\"total_delay_cost\":\"0.00\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 15:37:45\",\"is_delete\":0}','{\"project_code\":\"AH-Gurugram - CONST-02\",\"project_name\":\"Apollo Gurugram \\u2014 MRI Room Fit-out\",\"project_scope\":\"test\",\"location\":\"Gurugram\",\"hospital_name\":\"Apollo Hospitals, Gurugram\",\"contractor_name\":\"test\",\"zone_department\":\"MRI Room\",\"area_facility\":\"MRI Room\",\"project_type_id\":1,\"project_type_label\":\"Green Field\",\"project_spoc_name\":\"Mr.Operations\",\"responsibility_name\":\"Mr.Operations\",\"responsible_user_id\":null,\"planned_start_date\":\"2026-06-25\",\"planned_completion_date\":\"2026-06-30\",\"actual_completion_date\":\"2026-07-06\",\"target_revised_completion_date\":\"2026-06-23\",\"project_status\":\"delayed\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:27:22\"}',1,'2026-06-08 16:27:23',1,'2026-06-08 16:27:23','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(2,'delay_register',2,'update','{\"id\":2,\"project_id\":2,\"delay_title\":\"CSSD Uni-directional flow\",\"delay_description\":\"CSSD Uni-directional flow\",\"primary_delay_drivers\":\"Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment\",\"specific_event_description\":\"CSSD Uni-directional flow\",\"impacted_task\":\"ICU Headwall Installation\",\"root_cause_id\":4,\"root_cause_label\":\"Design Change\",\"delay_start_date\":\"2026-02-03\",\"delay_end_date\":\"2026-02-20\",\"target_revised_completion_date\":null,\"delay_days\":17,\"delay_category_id\":12,\"responsibility_user_id\":null,\"responsibility_name\":\"Dr X\",\"severity\":\"moderate\",\"licensing_openings_affected\":0,\"alert_level\":\"amber\",\"escalation_level\":2,\"register_status\":\"open\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 15:37:45\",\"is_delete\":0}','{\"project_id\":2,\"delay_title\":\"CSSD Uni-directional flow\",\"delay_description\":\"CSSD Uni-directional flow\",\"primary_delay_drivers\":\"Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment\",\"specific_event_description\":\"CSSD Uni-directional flow\",\"impacted_task\":\"ICU Headwall Installation\",\"root_cause_id\":4,\"root_cause_label\":\"Design Change\",\"delay_category_id\":12,\"responsibility_user_id\":null,\"responsibility_name\":\"Dr X\",\"delay_start_date\":\"2026-03-28\",\"delay_end_date\":\"2026-04-30\",\"target_revised_completion_date\":\"2026-05-28\",\"licensing_openings_affected\":0,\"register_status\":\"open\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:32:36\",\"delay_days\":33,\"severity\":\"critical\",\"alert_level\":\"red\",\"escalation_level\":3}',1,'2026-06-08 16:32:36',1,'2026-06-08 16:32:36','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(3,'delay_register',2,'update','{\"id\":2,\"project_id\":2,\"delay_title\":\"CSSD Uni-directional flow\",\"delay_description\":\"CSSD Uni-directional flow\",\"primary_delay_drivers\":\"Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment\",\"specific_event_description\":\"CSSD Uni-directional flow\",\"impacted_task\":\"ICU Headwall Installation\",\"root_cause_id\":4,\"root_cause_label\":\"Design Change\",\"delay_start_date\":\"2026-03-28\",\"delay_end_date\":\"2026-04-30\",\"target_revised_completion_date\":\"2026-05-28\",\"delay_days\":33,\"delay_category_id\":12,\"responsibility_user_id\":null,\"responsibility_name\":\"Dr X\",\"severity\":\"critical\",\"licensing_openings_affected\":0,\"alert_level\":\"red\",\"escalation_level\":3,\"register_status\":\"open\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:32:36\",\"is_delete\":0}','{\"project_id\":2,\"delay_title\":\"CSSD Uni-directional flow\",\"delay_description\":\"CSSD Uni-directional flow\",\"primary_delay_drivers\":\"Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment\",\"specific_event_description\":\"CSSD Uni-directional flow\",\"impacted_task\":\"ICU Headwall Installation\",\"root_cause_id\":4,\"root_cause_label\":\"Design Change\",\"delay_category_id\":12,\"responsibility_user_id\":null,\"responsibility_name\":\"Dr X\",\"delay_start_date\":\"2026-03-28\",\"delay_end_date\":\"2026-04-30\",\"target_revised_completion_date\":\"2026-05-28\",\"licensing_openings_affected\":0,\"register_status\":\"closed\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:32:59\",\"delay_days\":33,\"severity\":\"critical\",\"alert_level\":\"red\",\"escalation_level\":3}',1,'2026-06-08 16:32:59',1,'2026-06-08 16:32:59','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(4,'project',2,'update','{\"id\":2,\"project_code\":\"AH-Gurugram - CONST-02\",\"project_name\":\"Apollo Gurugram \\u2014 MRI Room Fit-out\",\"project_type_id\":1,\"project_type_label\":\"Green Field\",\"project_scope\":\"test\",\"location\":\"Gurugram\",\"hospital_name\":\"Apollo Hospitals, Gurugram\",\"contractor_name\":\"test\",\"zone_department\":\"MRI Room\",\"zone_id\":null,\"area_facility\":\"MRI Room\",\"responsible_user_id\":null,\"responsibility_name\":\"Mr.Operations\",\"project_spoc_name\":\"Mr.Operations\",\"planned_start_date\":\"2026-06-25\",\"planned_completion_date\":\"2026-06-30\",\"actual_completion_date\":\"2026-07-06\",\"target_revised_completion_date\":\"2026-06-23\",\"project_status\":\"delayed\",\"total_delay_cost\":\"0.00\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:32:59\",\"is_delete\":0}','{\"project_code\":\"AH-Gurugram - CONST-02\",\"project_name\":\"Apollo Gurugram \\u2014 MRI Room Fit-out\",\"project_scope\":\"test\",\"location\":\"Gurugram\",\"hospital_name\":\"Apollo Hospitals, Gurugram\",\"contractor_name\":\"test\",\"zone_id\":9,\"zone_department\":\"Central Zone\",\"area_facility\":\"MRI Room\",\"project_type_id\":1,\"project_type_label\":\"Green Field\",\"project_spoc_name\":\"Mr.Operations\",\"responsibility_name\":\"Mr.Operations\",\"responsible_user_id\":null,\"planned_start_date\":\"2026-06-25\",\"planned_completion_date\":\"2026-06-30\",\"actual_completion_date\":\"2026-07-06\",\"target_revised_completion_date\":\"2026-06-23\",\"project_status\":\"delayed\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:35:34\"}',1,'2026-06-08 16:35:34',1,'2026-06-08 16:35:34','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(5,'project',1,'update','{\"id\":1,\"project_code\":\"AH-Gurugram - CONST-01\",\"project_name\":\"Apollo Gurugram Annex \\u2014 Emergency Wing\",\"project_type_id\":1,\"project_type_label\":\"Green Field\",\"project_scope\":null,\"location\":\"Gurugram\",\"hospital_name\":\"Apollo Hospitals, Gurugram\",\"contractor_name\":null,\"zone_department\":\"Emergency\",\"zone_id\":null,\"area_facility\":\"Emergency\",\"responsible_user_id\":null,\"responsibility_name\":\"Mr.Biomedical\",\"project_spoc_name\":\"Mr.Biomedical\",\"planned_start_date\":\"2026-06-15\",\"planned_completion_date\":\"2026-06-21\",\"actual_completion_date\":null,\"target_revised_completion_date\":null,\"project_status\":\"delayed\",\"total_delay_cost\":\"0.00\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 15:37:45\",\"is_delete\":0}','{\"project_code\":\"AH-Gurugram - CONST-01\",\"project_name\":\"Apollo Gurugram Annex \\u2014 Emergency Wing\",\"project_scope\":\"\",\"location\":\"Gurugram\",\"hospital_name\":\"Apollo Hospitals, Gurugram\",\"contractor_name\":\"\",\"zone_id\":1,\"zone_department\":\"North Zone\",\"area_facility\":\"Emergency\",\"project_type_id\":1,\"project_type_label\":\"Green Field\",\"project_spoc_name\":\"Mr.Biomedical\",\"responsibility_name\":\"Mr.Biomedical\",\"responsible_user_id\":null,\"planned_start_date\":\"2026-06-15\",\"planned_completion_date\":\"2026-06-21\",\"actual_completion_date\":null,\"target_revised_completion_date\":null,\"project_status\":\"delayed\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:35:41\"}',1,'2026-06-08 16:35:41',1,'2026-06-08 16:35:41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(6,'delay_register',2,'update','{\"id\":2,\"project_id\":2,\"delay_title\":\"CSSD Uni-directional flow\",\"delay_description\":\"CSSD Uni-directional flow\",\"primary_delay_drivers\":\"Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment\",\"specific_event_description\":\"CSSD Uni-directional flow\",\"impacted_task\":\"ICU Headwall Installation\",\"root_cause_id\":4,\"root_cause_label\":\"Design Change\",\"delay_start_date\":\"2026-03-28\",\"delay_end_date\":\"2026-04-30\",\"target_revised_completion_date\":\"2026-05-28\",\"delay_days\":33,\"delay_category_id\":12,\"responsibility_user_id\":null,\"responsibility_name\":\"Dr X\",\"severity\":\"critical\",\"licensing_openings_affected\":0,\"alert_level\":\"red\",\"escalation_level\":3,\"register_status\":\"closed\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 16:32:59\",\"is_delete\":0}','{\"project_id\":2,\"delay_title\":\"CSSD Uni-directional flow\",\"delay_description\":\"CSSD Uni-directional flow\",\"primary_delay_drivers\":\"Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment\",\"specific_event_description\":\"CSSD Uni-directional flow\",\"impacted_task\":\"ICU Headwall Installation\",\"root_cause_id\":4,\"root_cause_label\":\"Design Change\",\"delay_category_id\":12,\"responsibility_user_id\":null,\"responsibility_name\":\"Dr X\",\"delay_start_date\":\"2026-03-28\",\"delay_end_date\":\"2026-04-30\",\"target_revised_completion_date\":\"2026-05-28\",\"licensing_openings_affected\":0,\"register_status\":\"in_progress\",\"updated_by\":1,\"updated_on\":\"2026-06-08 17:21:43\",\"delay_days\":33,\"severity\":\"critical\",\"alert_level\":\"red\",\"escalation_level\":3}',1,'2026-06-08 17:21:44',1,'2026-06-08 17:21:44','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(7,'delay_mitigation',2,'create',NULL,'{\"delay_register_id\":1,\"mitigation_action\":\"test\",\"owner_user_id\":null,\"owner_name\":\"test\",\"target_resolution_date\":\"2026-06-09\",\"current_status\":\"open\",\"resolution_remarks\":\"test\",\"updated_by\":1,\"updated_on\":\"2026-06-08 17:22:12\",\"created_by\":1,\"created_on\":\"2026-06-08 17:22:12\",\"is_delete\":0}',1,'2026-06-08 17:22:13',1,'2026-06-08 17:22:13','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(8,'delay_mitigation',3,'create',NULL,'{\"delay_register_id\":1,\"mitigation_action\":\"fdsfadsfsa\",\"owner_user_id\":null,\"owner_name\":\"asfdsaf\",\"target_resolution_date\":\"2026-06-09\",\"current_status\":\"open\",\"resolution_remarks\":\"fadsfdsf\",\"updated_by\":1,\"updated_on\":\"2026-06-08 17:22:27\",\"created_by\":1,\"created_on\":\"2026-06-08 17:22:27\",\"is_delete\":0}',1,'2026-06-08 17:22:27',1,'2026-06-08 17:22:27','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(9,'delay_financial_impact',1,'create',NULL,'{\"delay_register_id\":2,\"project_id\":2,\"labor_overrun\":150000,\"material_cost_overrun\":50000,\"contractor_claims\":78900000,\"equipment_storage_charges\":23456,\"delayed_admissions\":43242,\"delayed_surgeries\":3243214,\"delayed_revenue\":32143214,\"lost_operational_days\":432143214,\"updated_by\":1,\"updated_on\":\"2026-06-09 16:38:04\",\"created_by\":1,\"created_on\":\"2026-06-09 16:38:04\",\"is_delete\":0,\"direct_cost_total\":79123456,\"opportunity_cost_total\":467572884,\"total_project_delay_cost\":546696340}',1,'2026-06-09 16:38:04',1,'2026-06-09 16:38:04','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(10,'delay_attachment',1,'create',NULL,'{\"delay_register_id\":2,\"project_id\":2,\"attachment_type\":\"photo\",\"description\":\"fadsfasf\",\"updated_by\":1,\"updated_on\":\"2026-06-09 16:40:02\",\"created_by\":1,\"created_on\":\"2026-06-09 16:40:02\",\"is_delete\":0,\"file_name\":\"Screenshot 2026-01-08 140637.png\",\"file_path\":\"uploads\\/delay_attachments\\/1781003402_6a27f48ae851b.png\",\"mime_type\":\"image\\/png\",\"file_size\":442543,\"uploaded_by\":1,\"uploaded_on\":\"2026-06-09 16:40:02\"}',1,'2026-06-09 16:40:03',1,'2026-06-09 16:40:03','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(11,'renovation_project',2,'update','{\"id\":2,\"project_code\":\"REN-002\",\"project_name\":\"OPD Upgrade\",\"project_scope\":\"Outpatient department upgrade\",\"location\":null,\"zone_department_impacted\":\"OPD\",\"renovation_type\":\"Department Upgrade\",\"project_status\":\"planned\",\"final_handover_date\":null,\"escalation_status\":\"none\",\"remarks\":\"Minimal disruption\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-08 15:37:45\",\"is_delete\":0}','{\"project_code\":\"REN-002\",\"project_name\":\"OPD Upgrade\",\"project_scope\":\"Outpatient department upgrade\",\"location\":\"\",\"zone_department_impacted\":\"OPD\",\"renovation_type\":\"Department Upgrade\",\"project_status\":\"planned\",\"final_handover_date\":null,\"escalation_status\":\"none\",\"remarks\":\"Minimal disruption afadsf\",\"updated_by\":1,\"updated_on\":\"2026-06-09 18:20:33\"}',1,'2026-06-09 18:20:33',1,'2026-06-09 18:20:33','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(12,'renovation_project',2,'update','{\"id\":2,\"project_code\":\"REN-002\",\"project_name\":\"OPD Upgrade\",\"project_scope\":\"Outpatient department upgrade\",\"location\":\"\",\"zone_department_impacted\":\"OPD\",\"renovation_type\":\"Department Upgrade\",\"project_status\":\"planned\",\"final_handover_date\":null,\"escalation_status\":\"none\",\"remarks\":\"Minimal disruption afadsf\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-09 18:20:33\",\"is_delete\":0}','{\"project_code\":\"REN-002\",\"project_name\":\"OPD Upgrade\",\"project_scope\":\"Outpatient department upgrade\",\"location\":\"\",\"zone_department_impacted\":\"OPD\",\"renovation_type\":\"Department Upgrade\",\"project_status\":\"planned\",\"final_handover_date\":null,\"escalation_status\":\"none\",\"remarks\":\"Minimal disruption afadsf\",\"updated_by\":1,\"updated_on\":\"2026-06-09 18:20:43\"}',1,'2026-06-09 18:20:43',1,'2026-06-09 18:20:43','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(13,'renovation_project',2,'update','{\"id\":2,\"project_code\":\"REN-002\",\"project_name\":\"OPD Upgrade\",\"project_scope\":\"Outpatient department upgrade\",\"location\":\"\",\"zone_department_impacted\":\"OPD\",\"renovation_type\":\"Department Upgrade\",\"project_status\":\"planned\",\"final_handover_date\":null,\"escalation_status\":\"none\",\"remarks\":\"Minimal disruption afadsf\",\"created_by\":1,\"created_on\":\"2026-06-08 15:37:45\",\"updated_by\":1,\"updated_on\":\"2026-06-09 18:20:43\",\"is_delete\":0}','{\"project_code\":\"REN-002\",\"project_name\":\"OPD Upgrade\",\"project_scope\":\"Outpatient department upgrade\",\"location\":\"\",\"zone_department_impacted\":\"OPD\",\"renovation_type\":\"Department Upgrade\",\"project_status\":\"planned\",\"final_handover_date\":null,\"escalation_status\":\"escalated\",\"remarks\":\"Minimal disruption afadsf\",\"updated_by\":1,\"updated_on\":\"2026-06-09 18:22:59\"}',1,'2026-06-09 18:22:59',1,'2026-06-09 18:22:59','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36'),
(14,'delay_register',3,'create',NULL,'{\"project_id\":2,\"delay_title\":\"test\",\"delay_description\":\"test\",\"primary_delay_drivers\":\"test\",\"specific_event_description\":\"test\",\"impacted_task\":\"test\",\"root_cause_id\":6,\"root_cause_label\":\"Resource Constraint\",\"delay_category_id\":18,\"responsibility_user_id\":null,\"responsibility_name\":\"test\",\"delay_start_date\":\"2026-06-17\",\"delay_end_date\":\"2026-06-26\",\"target_revised_completion_date\":\"2026-06-30\",\"licensing_openings_affected\":1,\"register_status\":\"open\",\"updated_by\":1,\"updated_on\":\"2026-06-10 19:35:40\",\"created_by\":1,\"created_on\":\"2026-06-10 19:35:40\",\"is_delete\":0,\"delay_days\":9,\"severity\":\"showstopper\",\"alert_level\":\"black\",\"escalation_level\":4}',1,'2026-06-10 19:35:41',1,'2026-06-10 19:35:41','::1','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36');

/*Table structure for table `tbl_delay_attachments` */

DROP TABLE IF EXISTS `tbl_delay_attachments`;

CREATE TABLE `tbl_delay_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delay_register_id` int(11) NOT NULL COMMENT 'tbl_delay_registers.id',
  `project_id` int(11) DEFAULT NULL COMMENT 'tbl_projects.id',
  `attachment_type` varchar(50) NOT NULL COMMENT 'photo, drawing, noc, approval_letter, vendor_communication, change_order, other',
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `file_size` int(10) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `uploaded_by` int(11) DEFAULT NULL COMMENT 'tbl_user.id',
  `uploaded_on` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_delay_attachments_delay_register_id_index` (`delay_register_id`),
  KEY `tbl_delay_attachments_project_id_index` (`project_id`),
  KEY `tbl_delay_attachments_attachment_type_index` (`attachment_type`),
  KEY `tbl_delay_attachments_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_delay_attachments` */

insert  into `tbl_delay_attachments`(`id`,`delay_register_id`,`project_id`,`attachment_type`,`file_name`,`file_path`,`mime_type`,`file_size`,`description`,`uploaded_by`,`uploaded_on`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,2,2,'photo','Screenshot 2026-01-08 140637.png','uploads/delay_attachments/1781003402_6a27f48ae851b.png','image/png',442543,'fadsfasf',1,'2026-06-09 16:40:02',1,'2026-06-09 16:40:02',1,'2026-06-09 16:40:02',0);

/*Table structure for table `tbl_delay_categories` */

DROP TABLE IF EXISTS `tbl_delay_categories`;

CREATE TABLE `tbl_delay_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_delay_categories_category_name_index` (`category_name`),
  KEY `tbl_delay_categories_status_index` (`status`),
  KEY `tbl_delay_categories_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_delay_categories` */

insert  into `tbl_delay_categories`(`id`,`category_name`,`description`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'Approval / NOC delay',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(2,'Material / procurement delay',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(3,'Contractor / vendor delay',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(4,'Design / scope change',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(5,'Infection control clearance',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(6,'Licensing / regulatory',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(7,'Resource unavailability',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(8,'Other',NULL,0,1,'2026-06-08 12:47:40',1,'2026-06-08 15:37:42',1),
(9,'Regulatory & Permitting','Long wait times for environmental, fire safety, PCPNDT, AERB approvals',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(10,'MEP','Mechanical, electrical, and plumbing delays affecting critical path',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(11,'Supply Chain & Procurement','Long-lead items (MRI, custom AHUs) delayed by vendor halting final inspection',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(12,'Design & Scope','Mid-construction changes; late-stage clinician requests for layout or equipment',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(13,'Medical Equipment Installations','Delays in medical equipment delivery, installation, or commissioning',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(14,'Specialized Labor','Shortage of certified manpower (e.g. lead-shielding installers)',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(15,'Logistical Challenges','Material movement blocked by active patient traffic; night-work delays',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(16,'Site Condition Surprises','Unplanned structural, plumbing, or site discovery issues',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(17,'Operational Readiness','Staff hiring/training delays; IT systems not ready for go-live',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0),
(18,'Infection Control Compliance','Dust barriers, air pressure, or infection control clearance delays',1,1,'2026-06-08 15:37:42',1,'2026-06-08 16:34:40',0);

/*Table structure for table `tbl_delay_financial_impacts` */

DROP TABLE IF EXISTS `tbl_delay_financial_impacts`;

CREATE TABLE `tbl_delay_financial_impacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delay_register_id` int(11) NOT NULL COMMENT 'tbl_delay_registers.id',
  `project_id` int(11) DEFAULT NULL COMMENT 'tbl_projects.id',
  `labor_overrun` decimal(15,2) NOT NULL DEFAULT 0.00,
  `material_cost_overrun` decimal(15,2) NOT NULL DEFAULT 0.00,
  `contractor_claims` decimal(15,2) NOT NULL DEFAULT 0.00,
  `equipment_storage_charges` decimal(15,2) NOT NULL DEFAULT 0.00,
  `direct_cost_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delayed_admissions` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delayed_surgeries` decimal(15,2) NOT NULL DEFAULT 0.00,
  `delayed_revenue` decimal(15,2) NOT NULL DEFAULT 0.00,
  `lost_operational_days` decimal(15,2) NOT NULL DEFAULT 0.00,
  `opportunity_cost_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total_project_delay_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_delay_financial_impacts_delay_register_id_index` (`delay_register_id`),
  KEY `tbl_delay_financial_impacts_project_id_index` (`project_id`),
  KEY `tbl_delay_financial_impacts_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_delay_financial_impacts` */

insert  into `tbl_delay_financial_impacts`(`id`,`delay_register_id`,`project_id`,`labor_overrun`,`material_cost_overrun`,`contractor_claims`,`equipment_storage_charges`,`direct_cost_total`,`delayed_admissions`,`delayed_surgeries`,`delayed_revenue`,`lost_operational_days`,`opportunity_cost_total`,`total_project_delay_cost`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,2,2,150000.00,50000.00,78900000.00,23456.00,79123456.00,43242.00,3243214.00,32143214.00,432143214.00,467572884.00,546696340.00,1,'2026-06-09 16:38:04',1,'2026-06-09 16:38:04',0);

/*Table structure for table `tbl_delay_mitigations` */

DROP TABLE IF EXISTS `tbl_delay_mitigations`;

CREATE TABLE `tbl_delay_mitigations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `delay_register_id` int(11) NOT NULL COMMENT 'tbl_delay_registers.id',
  `mitigation_action` text DEFAULT NULL,
  `owner_user_id` int(11) DEFAULT NULL COMMENT 'tbl_user.id',
  `owner_name` varchar(255) DEFAULT NULL,
  `target_resolution_date` date DEFAULT NULL,
  `current_status` varchar(30) NOT NULL DEFAULT 'open' COMMENT 'open, in_progress, escalated, closed',
  `resolution_remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_delay_mitigations_delay_register_id_index` (`delay_register_id`),
  KEY `tbl_delay_mitigations_owner_user_id_index` (`owner_user_id`),
  KEY `tbl_delay_mitigations_current_status_index` (`current_status`),
  KEY `tbl_delay_mitigations_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_delay_mitigations` */

insert  into `tbl_delay_mitigations`(`id`,`delay_register_id`,`mitigation_action`,`owner_user_id`,`owner_name`,`target_resolution_date`,`current_status`,`resolution_remarks`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,2,'Change Order \n#42 outlets issued',NULL,NULL,NULL,'in_progress',NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,1,'test',NULL,'test','2026-06-09','open','test',1,'2026-06-08 17:22:12',1,'2026-06-08 17:22:12',0),
(3,1,'fdsfadsfsa',NULL,'asfdsaf','2026-06-09','open','fadsfdsf',1,'2026-06-08 17:22:27',1,'2026-06-08 17:22:27',0);

/*Table structure for table `tbl_delay_registers` */

DROP TABLE IF EXISTS `tbl_delay_registers`;

CREATE TABLE `tbl_delay_registers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT 'tbl_projects.id',
  `delay_title` varchar(255) DEFAULT NULL,
  `delay_description` text DEFAULT NULL,
  `primary_delay_drivers` text DEFAULT NULL,
  `specific_event_description` text DEFAULT NULL,
  `impacted_task` varchar(255) DEFAULT NULL COMMENT 'Critical path / impacted task',
  `root_cause_id` int(11) DEFAULT NULL COMMENT 'tbl_root_causes.id',
  `root_cause_label` varchar(150) DEFAULT NULL,
  `delay_start_date` date DEFAULT NULL,
  `delay_end_date` date DEFAULT NULL,
  `target_revised_completion_date` date DEFAULT NULL,
  `delay_days` int(11) NOT NULL DEFAULT 0 COMMENT 'End Date - Start Date; auto-updated in app',
  `delay_category_id` int(11) DEFAULT NULL COMMENT 'tbl_delay_categories.id',
  `responsibility_user_id` int(11) DEFAULT NULL COMMENT 'tbl_user.id',
  `responsibility_name` varchar(255) DEFAULT NULL,
  `severity` varchar(30) NOT NULL DEFAULT 'minor' COMMENT 'minor, moderate, critical, showstopper',
  `licensing_openings_affected` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 = Showstopper regardless of days',
  `alert_level` varchar(20) NOT NULL DEFAULT 'green' COMMENT 'green, amber, red, black',
  `escalation_level` tinyint(3) unsigned DEFAULT NULL COMMENT '1-4 per escalation matrix',
  `register_status` varchar(30) NOT NULL DEFAULT 'open' COMMENT 'open, in_progress, closed',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_delay_registers_project_id_index` (`project_id`),
  KEY `tbl_delay_registers_delay_category_id_index` (`delay_category_id`),
  KEY `tbl_delay_registers_severity_index` (`severity`),
  KEY `tbl_delay_registers_alert_level_index` (`alert_level`),
  KEY `tbl_delay_registers_escalation_level_index` (`escalation_level`),
  KEY `tbl_delay_registers_delay_start_date_index` (`delay_start_date`),
  KEY `tbl_delay_registers_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_delay_registers` */

insert  into `tbl_delay_registers`(`id`,`project_id`,`delay_title`,`delay_description`,`primary_delay_drivers`,`specific_event_description`,`impacted_task`,`root_cause_id`,`root_cause_label`,`delay_start_date`,`delay_end_date`,`target_revised_completion_date`,`delay_days`,`delay_category_id`,`responsibility_user_id`,`responsibility_name`,`severity`,`licensing_openings_affected`,`alert_level`,`escalation_level`,`register_status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,'Pending Fire inspection for Annex building','Pending Fire inspection for Annex building','Long wait times for environmental, fire safety, PCPNDT, AERB approvals','Pending Fire inspection for Annex building','Final NOC',1,'Planning Gap',NULL,NULL,NULL,0,9,NULL,NULL,'showstopper',1,'black',4,'open',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,'CSSD Uni-directional flow','CSSD Uni-directional flow','Mid-construction changes. Late-stage requests from clinicians to change room layouts or equipment','CSSD Uni-directional flow','ICU Headwall Installation',4,'Design Change','2026-03-28','2026-04-30','2026-05-28',33,12,NULL,'Dr X','critical',0,'red',3,'in_progress',1,'2026-06-08 15:37:45',1,'2026-06-08 17:21:43',0),
(3,2,'test','test','test','test','test',6,'Resource Constraint','2026-06-17','2026-06-26','2026-06-30',9,18,NULL,'test','showstopper',1,'black',4,'open',1,'2026-06-10 19:35:40',1,'2026-06-10 19:35:40',0);

/*Table structure for table `tbl_delay_severity_rules` */

DROP TABLE IF EXISTS `tbl_delay_severity_rules`;

CREATE TABLE `tbl_delay_severity_rules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `severity_code` varchar(30) NOT NULL COMMENT 'minor, moderate, critical, showstopper',
  `severity_label` varchar(100) NOT NULL,
  `min_delay_days` int(10) unsigned DEFAULT NULL,
  `max_delay_days` int(10) unsigned DEFAULT NULL,
  `requires_licensing_flag` tinyint(4) NOT NULL DEFAULT 0 COMMENT '1 for showstopper rule',
  `default_escalation_level` tinyint(3) unsigned DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_delay_severity_rules_severity_code_unique` (`severity_code`),
  KEY `tbl_delay_severity_rules_status_index` (`status`),
  KEY `tbl_delay_severity_rules_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_delay_severity_rules` */

insert  into `tbl_delay_severity_rules`(`id`,`severity_code`,`severity_label`,`min_delay_days`,`max_delay_days`,`requires_licensing_flag`,`default_escalation_level`,`sort_order`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'minor','Minor (1-7 days)',1,7,0,1,1,1,1,'2026-06-08 12:47:40',1,'2026-06-08 16:34:40',0),
(2,'moderate','Moderate (8-30 days)',8,30,0,2,2,1,1,'2026-06-08 12:47:40',1,'2026-06-08 16:34:40',0),
(3,'critical','Critical (>30 days)',31,NULL,0,3,3,1,1,'2026-06-08 12:47:40',1,'2026-06-08 16:34:40',0),
(4,'showstopper','Showstopper (impacts licensing/opening)',NULL,NULL,1,4,4,1,1,'2026-06-08 12:47:40',1,'2026-06-08 16:34:40',0);

/*Table structure for table `tbl_ews_alert_levels` */

DROP TABLE IF EXISTS `tbl_ews_alert_levels`;

CREATE TABLE `tbl_ews_alert_levels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `level_code` varchar(20) NOT NULL COMMENT 'green, amber, red, black',
  `level_label` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_ews_alert_levels_level_code_unique` (`level_code`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_ews_alert_levels` */

insert  into `tbl_ews_alert_levels`(`id`,`level_code`,`level_label`,`description`,`sort_order`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'green','Green — On Track',NULL,1,1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0),
(2,'amber','Amber — Potential Delay',NULL,2,1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0),
(3,'red','Red — Critical Delay',NULL,3,1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0),
(4,'black','Black — Showstopper',NULL,4,1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0);

/*Table structure for table `tbl_ews_escalation_matrix` */

DROP TABLE IF EXISTS `tbl_ews_escalation_matrix`;

CREATE TABLE `tbl_ews_escalation_matrix` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `escalation_level` tinyint(3) unsigned NOT NULL COMMENT '1-4',
  `escalation_role` varchar(150) NOT NULL COMMENT 'Project SPOC, Department Head, etc.',
  `trigger_severity` varchar(30) DEFAULT NULL COMMENT 'minor, moderate, critical, showstopper',
  `trigger_alert_level` varchar(20) DEFAULT NULL COMMENT 'green, amber, red, black',
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_ews_escalation_matrix_escalation_level_unique` (`escalation_level`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_ews_escalation_matrix` */

insert  into `tbl_ews_escalation_matrix`(`id`,`escalation_level`,`escalation_role`,`trigger_severity`,`trigger_alert_level`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,'Project SPOC','minor','green',1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0),
(2,2,'Department Head','moderate','amber',1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0),
(3,3,'Project Steering Committee','critical','red',1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0),
(4,4,'Management','showstopper','black',1,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0);

/*Table structure for table `tbl_ews_potential_delay_alerts` */

DROP TABLE IF EXISTS `tbl_ews_potential_delay_alerts`;

CREATE TABLE `tbl_ews_potential_delay_alerts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_task_id` int(11) DEFAULT NULL COMMENT 'tbl_renovation_tasks.id',
  `project_id` int(11) DEFAULT NULL COMMENT 'tbl_projects.id',
  `task_completion_percent` decimal(5,2) DEFAULT NULL,
  `consumed_duration_percent` decimal(5,2) DEFAULT NULL,
  `allocated_duration_days` int(10) unsigned DEFAULT NULL,
  `elapsed_duration_days` int(10) unsigned DEFAULT NULL,
  `alert_level` varchar(20) NOT NULL DEFAULT 'amber' COMMENT 'green, amber, red, black',
  `alert_status` varchar(30) NOT NULL DEFAULT 'open' COMMENT 'open, acknowledged, closed',
  `alert_message` text DEFAULT NULL,
  `alert_generated_on` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_ews_potential_delay_alerts_renovation_task_id_index` (`renovation_task_id`),
  KEY `tbl_ews_potential_delay_alerts_project_id_index` (`project_id`),
  KEY `tbl_ews_potential_delay_alerts_alert_level_index` (`alert_level`),
  KEY `tbl_ews_potential_delay_alerts_alert_status_index` (`alert_status`),
  KEY `tbl_ews_potential_delay_alerts_is_delete_index` (`is_delete`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_ews_potential_delay_alerts` */

/*Table structure for table `tbl_ews_prediction_config` */

DROP TABLE IF EXISTS `tbl_ews_prediction_config`;

CREATE TABLE `tbl_ews_prediction_config` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `config_key` varchar(100) NOT NULL,
  `config_value` varchar(255) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_ews_prediction_config_config_key_unique` (`config_key`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_ews_prediction_config` */

insert  into `tbl_ews_prediction_config`(`id`,`config_key`,`config_value`,`description`,`status`,`updated_by`,`updated_on`) values 
(1,'max_task_completion_percent','50','Task completion must be below this % for EWS alert',1,1,'2026-06-08 12:47:40'),
(2,'min_consumed_duration_percent','80','Consumed duration must exceed this % for EWS alert',1,1,'2026-06-08 12:47:40');

/*Table structure for table `tbl_notification_logs` */

DROP TABLE IF EXISTS `tbl_notification_logs`;

CREATE TABLE `tbl_notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `notification_type` enum('specific_user','multiple_users','all_users','by_role','by_event','by_conference','by_session','by_platform','anonymous') NOT NULL COMMENT 'Type of notification sent',
  `title` varchar(255) NOT NULL COMMENT 'Notification title',
  `body` text NOT NULL COMMENT 'Notification body',
  `sent_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of notifications sent successfully',
  `failed_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Number of notifications failed',
  `total_count` int(11) NOT NULL DEFAULT 0 COMMENT 'Total number of notifications attempted',
  `created_by` int(10) unsigned DEFAULT NULL COMMENT 'FK to tbl_user - Admin who sent the notification',
  `created_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tbl_notification_logs_notification_type_index` (`notification_type`),
  KEY `tbl_notification_logs_created_by_index` (`created_by`),
  KEY `tbl_notification_logs_created_on_index` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_notification_logs` */

/*Table structure for table `tbl_project_types` */

DROP TABLE IF EXISTS `tbl_project_types`;

CREATE TABLE `tbl_project_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `type_code` varchar(50) NOT NULL,
  `type_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_project_types_type_code_unique` (`type_code`),
  KEY `tbl_project_types_status_index` (`status`),
  KEY `tbl_project_types_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_project_types` */

insert  into `tbl_project_types`(`id`,`type_code`,`type_name`,`description`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'green_field','Green Field','New hospital construction on undeveloped land',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(2,'brown_field','Brown Field','Expansion or rebuild on existing hospital site',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(3,'renovation','Renovation','Renovation of existing departments or facilities',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0);

/*Table structure for table `tbl_projects` */

DROP TABLE IF EXISTS `tbl_projects`;

CREATE TABLE `tbl_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `project_type_id` int(11) DEFAULT NULL COMMENT 'tbl_project_types.id',
  `project_type_label` varchar(100) DEFAULT NULL COMMENT 'Green Field, Brown Field, Renovation',
  `project_scope` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `hospital_name` varchar(255) DEFAULT NULL COMMENT 'For delay analytics by hospital',
  `contractor_name` varchar(255) DEFAULT NULL COMMENT 'For delay analytics by contractor',
  `zone_department` varchar(255) DEFAULT NULL,
  `zone_id` int(11) DEFAULT NULL COMMENT 'tbl_zones.id',
  `area_facility` varchar(255) DEFAULT NULL COMMENT 'Area / Facility per Excel',
  `responsible_user_id` int(11) DEFAULT NULL COMMENT 'tbl_user.id — Project SPOC',
  `responsibility_name` varchar(255) DEFAULT NULL,
  `project_spoc_name` varchar(255) DEFAULT NULL COMMENT 'Project SPOC display name',
  `planned_start_date` date DEFAULT NULL,
  `planned_completion_date` date DEFAULT NULL,
  `actual_completion_date` date DEFAULT NULL,
  `target_revised_completion_date` date DEFAULT NULL,
  `project_status` varchar(30) NOT NULL DEFAULT 'active' COMMENT 'active, delayed, completed, on_hold',
  `total_delay_cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Roll-up: direct + opportunity',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_projects_project_code_unique` (`project_code`),
  KEY `tbl_projects_hospital_name_index` (`hospital_name`),
  KEY `tbl_projects_contractor_name_index` (`contractor_name`),
  KEY `tbl_projects_responsible_user_id_index` (`responsible_user_id`),
  KEY `tbl_projects_project_status_index` (`project_status`),
  KEY `tbl_projects_planned_completion_date_index` (`planned_completion_date`),
  KEY `tbl_projects_is_delete_index` (`is_delete`),
  KEY `tbl_projects_zone_id_index` (`zone_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_projects` */

insert  into `tbl_projects`(`id`,`project_code`,`project_name`,`project_type_id`,`project_type_label`,`project_scope`,`location`,`hospital_name`,`contractor_name`,`zone_department`,`zone_id`,`area_facility`,`responsible_user_id`,`responsibility_name`,`project_spoc_name`,`planned_start_date`,`planned_completion_date`,`actual_completion_date`,`target_revised_completion_date`,`project_status`,`total_delay_cost`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'AH-Gurugram - CONST-01','Apollo Gurugram Annex — Emergency Wing',1,'Green Field','','Gurugram','Apollo Hospitals, Gurugram','','North Zone',1,'Emergency',NULL,'Mr.Biomedical','Mr.Biomedical','2026-06-15','2026-06-21',NULL,NULL,'delayed',0.00,1,'2026-06-08 15:37:45',1,'2026-06-08 16:35:41',0),
(2,'AH-Gurugram - CONST-02','Apollo Gurugram — MRI Room Fit-out',1,'Green Field','test','Gurugram','Apollo Hospitals, Gurugram','test','Central Zone',9,'MRI Room',NULL,'Mr.Operations','Mr.Operations','2026-06-25','2026-06-30','2026-07-06','2026-06-23','delayed',546696340.00,1,'2026-06-08 15:37:45',1,'2026-06-10 19:35:40',0);

/*Table structure for table `tbl_renovation_approvals` */

DROP TABLE IF EXISTS `tbl_renovation_approvals`;

CREATE TABLE `tbl_renovation_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `renovation_task_id` int(11) DEFAULT NULL COMMENT 'tbl_renovation_tasks.id',
  `approval_type` varchar(100) DEFAULT NULL,
  `approval_status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `submitted_date` date DEFAULT NULL,
  `approved_date` date DEFAULT NULL,
  `approval_pending_days` int(10) unsigned NOT NULL DEFAULT 0,
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_approvals_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_approvals_approval_status_index` (`approval_status`),
  KEY `tbl_renovation_approvals_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_approvals` */

insert  into `tbl_renovation_approvals`(`id`,`renovation_project_id`,`renovation_task_id`,`approval_type`,`approval_status`,`submitted_date`,`approved_date`,`approval_pending_days`,`remarks`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,1,NULL,'pending',NULL,NULL,7,NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,2,NULL,'approved',NULL,NULL,0,NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_change_orders` */

DROP TABLE IF EXISTS `tbl_renovation_change_orders`;

CREATE TABLE `tbl_renovation_change_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `change_order_number` varchar(50) DEFAULT NULL,
  `change_description` text DEFAULT NULL,
  `approval_status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `change_cost` decimal(15,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_change_orders_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_change_orders_approval_status_index` (`approval_status`),
  KEY `tbl_renovation_change_orders_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_change_orders` */

insert  into `tbl_renovation_change_orders`(`id`,`renovation_project_id`,`change_order_number`,`change_description`,`approval_status`,`change_cost`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,'CO-001','Scope adjustments per clinical feedback','approved',NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,1,'CO-002','Scope adjustments per clinical feedback','approved',NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_cost_tracking` */

DROP TABLE IF EXISTS `tbl_renovation_cost_tracking`;

CREATE TABLE `tbl_renovation_cost_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `budgeted_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `actual_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `cost_overrun_percent` decimal(8,2) NOT NULL DEFAULT 0.00,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_cost_tracking_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_cost_tracking_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_cost_tracking` */

insert  into `tbl_renovation_cost_tracking`(`id`,`renovation_project_id`,`budgeted_cost`,`actual_cost`,`cost_overrun_percent`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,500000.00,575000.00,15.00,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,150000.00,0.00,0.00,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_daily_delay_logs` */

DROP TABLE IF EXISTS `tbl_renovation_daily_delay_logs`;

CREATE TABLE `tbl_renovation_daily_delay_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `renovation_task_id` int(11) DEFAULT NULL COMMENT 'tbl_renovation_tasks.id',
  `log_date` date NOT NULL,
  `delay_reason` text DEFAULT NULL,
  `entered_by` int(11) DEFAULT NULL COMMENT 'tbl_user.id',
  `corrective_action` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_daily_delay_logs_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_daily_delay_logs_renovation_task_id_index` (`renovation_task_id`),
  KEY `tbl_renovation_daily_delay_logs_log_date_index` (`log_date`),
  KEY `tbl_renovation_daily_delay_logs_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_daily_delay_logs` */

insert  into `tbl_renovation_daily_delay_logs`(`id`,`renovation_project_id`,`renovation_task_id`,`log_date`,`delay_reason`,`entered_by`,`corrective_action`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,1,'2026-06-10','Material shortage',1,'Expedited vendor delivery',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_operational_impacts` */

DROP TABLE IF EXISTS `tbl_renovation_operational_impacts`;

CREATE TABLE `tbl_renovation_operational_impacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `shutdown_required` varchar(10) NOT NULL DEFAULT 'no' COMMENT 'yes, no',
  `patient_service_disruption_score` tinyint(3) unsigned DEFAULT NULL COMMENT '1-10',
  `temporary_relocation_needed` varchar(10) NOT NULL DEFAULT 'no' COMMENT 'yes, no',
  `infection_control_clearance` varchar(20) NOT NULL DEFAULT 'pending' COMMENT 'pending, approved, rejected',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `reno_op_impacts_project_idx` (`renovation_project_id`),
  KEY `reno_op_impacts_ic_clearance_idx` (`infection_control_clearance`),
  KEY `reno_op_impacts_is_delete_idx` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_operational_impacts` */

insert  into `tbl_renovation_operational_impacts`(`id`,`renovation_project_id`,`shutdown_required`,`patient_service_disruption_score`,`temporary_relocation_needed`,`infection_control_clearance`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,'yes',9,'yes','approved',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,'no',4,'no','approved',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_procurements` */

DROP TABLE IF EXISTS `tbl_renovation_procurements`;

CREATE TABLE `tbl_renovation_procurements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `renovation_task_id` int(11) DEFAULT NULL COMMENT 'tbl_renovation_tasks.id',
  `vendor_contractor` varchar(255) DEFAULT NULL,
  `procurement_status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, ordered, in_transit, delivered, installed',
  `material_delay_days` int(10) unsigned NOT NULL DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_procurements_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_procurements_procurement_status_index` (`procurement_status`),
  KEY `tbl_renovation_procurements_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_procurements` */

insert  into `tbl_renovation_procurements`(`id`,`renovation_project_id`,`renovation_task_id`,`vendor_contractor`,`procurement_status`,`material_delay_days`,`notes`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,1,'ABC Infra','ordered',5,NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,2,'XYZ Electrical','pending',0,NULL,1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_projects` */

DROP TABLE IF EXISTS `tbl_renovation_projects`;

CREATE TABLE `tbl_renovation_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_code` varchar(50) NOT NULL COMMENT 'Project ID',
  `project_name` varchar(255) NOT NULL,
  `project_scope` text DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `zone_department_impacted` varchar(255) DEFAULT NULL,
  `renovation_type` varchar(150) DEFAULT NULL,
  `project_status` varchar(30) NOT NULL DEFAULT 'active',
  `final_handover_date` date DEFAULT NULL,
  `escalation_status` varchar(50) DEFAULT NULL COMMENT 'none, escalated, etc.',
  `remarks` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_renovation_projects_project_code_unique` (`project_code`),
  KEY `tbl_renovation_projects_project_status_index` (`project_status`),
  KEY `tbl_renovation_projects_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_projects` */

insert  into `tbl_renovation_projects`(`id`,`project_code`,`project_name`,`project_scope`,`location`,`zone_department_impacted`,`renovation_type`,`project_status`,`final_handover_date`,`escalation_status`,`remarks`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'REN-001','ICU Renovation','Critical care area renovation',NULL,'ICU','Department Renovation','in_progress','2026-06-20','escalated','Critical care area',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,'REN-002','OPD Upgrade','Outpatient department upgrade','','OPD','Department Upgrade','planned',NULL,'escalated','Minimal disruption afadsf',1,'2026-06-08 15:37:45',1,'2026-06-09 18:22:59',0);

/*Table structure for table `tbl_renovation_risk_assessments` */

DROP TABLE IF EXISTS `tbl_renovation_risk_assessments`;

CREATE TABLE `tbl_renovation_risk_assessments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `renovation_task_id` int(11) DEFAULT NULL COMMENT 'tbl_renovation_tasks.id',
  `delay_days` int(11) NOT NULL DEFAULT 0,
  `disruption_score` tinyint(3) unsigned DEFAULT NULL,
  `approval_delay_days` int(11) NOT NULL DEFAULT 0,
  `material_delay_days` int(11) NOT NULL DEFAULT 0,
  `dependency_delay_days` int(11) NOT NULL DEFAULT 0,
  `risk_score` tinyint(3) unsigned DEFAULT NULL COMMENT 'Numeric score 1-10 per Excel',
  `risk_level` varchar(30) NOT NULL DEFAULT 'low' COMMENT 'low, medium, high, critical',
  `assessment_notes` text DEFAULT NULL,
  `assessed_on` datetime DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_risk_assessments_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_risk_assessments_renovation_task_id_index` (`renovation_task_id`),
  KEY `tbl_renovation_risk_assessments_risk_level_index` (`risk_level`),
  KEY `tbl_renovation_risk_assessments_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_risk_assessments` */

insert  into `tbl_renovation_risk_assessments`(`id`,`renovation_project_id`,`renovation_task_id`,`delay_days`,`disruption_score`,`approval_delay_days`,`material_delay_days`,`dependency_delay_days`,`risk_score`,`risk_level`,`assessment_notes`,`assessed_on`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,1,3,9,7,5,0,8,'high','Excel risk score 8 — mapped to high risk band','2026-06-08 15:37:45',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,2,0,4,0,0,0,5,'medium','Excel risk score 5','2026-06-08 15:37:45',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_task_dependencies` */

DROP TABLE IF EXISTS `tbl_renovation_task_dependencies`;

CREATE TABLE `tbl_renovation_task_dependencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_task_id` int(11) NOT NULL COMMENT 'tbl_renovation_tasks.id — dependent task',
  `dependency_task_id` int(11) NOT NULL COMMENT 'tbl_renovation_tasks.id — prerequisite',
  `dependency_status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, in_progress, completed, blocked',
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_task_dependencies_renovation_task_id_index` (`renovation_task_id`),
  KEY `tbl_renovation_task_dependencies_dependency_task_id_index` (`dependency_task_id`),
  KEY `tbl_renovation_task_dependencies_dependency_status_index` (`dependency_status`),
  KEY `tbl_renovation_task_dependencies_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_task_dependencies` */

insert  into `tbl_renovation_task_dependencies`(`id`,`renovation_task_id`,`dependency_task_id`,`dependency_status`,`notes`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,0,'completed','Design Approval',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,0,'pending','Vendor Finalization',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_renovation_tasks` */

DROP TABLE IF EXISTS `tbl_renovation_tasks`;

CREATE TABLE `tbl_renovation_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `renovation_project_id` int(11) NOT NULL COMMENT 'tbl_renovation_projects.id',
  `task_category` varchar(150) DEFAULT NULL,
  `task_description` text DEFAULT NULL,
  `priority` varchar(20) NOT NULL DEFAULT 'medium' COMMENT 'high, medium, low',
  `planned_start_date` date DEFAULT NULL,
  `planned_end_date` date DEFAULT NULL,
  `actual_start_date` date DEFAULT NULL,
  `actual_end_date` date DEFAULT NULL,
  `allocated_duration_days` int(10) unsigned DEFAULT NULL,
  `elapsed_duration_days` int(10) unsigned DEFAULT NULL,
  `task_completion_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `consumed_duration_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  `task_status` varchar(30) NOT NULL DEFAULT 'pending' COMMENT 'pending, in_progress, completed, delayed, blocked',
  `risk_level` varchar(30) DEFAULT NULL COMMENT 'low, medium, high, critical',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `tbl_renovation_tasks_renovation_project_id_index` (`renovation_project_id`),
  KEY `tbl_renovation_tasks_priority_index` (`priority`),
  KEY `tbl_renovation_tasks_task_status_index` (`task_status`),
  KEY `tbl_renovation_tasks_risk_level_index` (`risk_level`),
  KEY `tbl_renovation_tasks_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_renovation_tasks` */

insert  into `tbl_renovation_tasks`(`id`,`renovation_project_id`,`task_category`,`task_description`,`priority`,`planned_start_date`,`planned_end_date`,`actual_start_date`,`actual_end_date`,`allocated_duration_days`,`elapsed_duration_days`,`task_completion_percent`,`consumed_duration_percent`,`task_status`,`risk_level`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,1,'Construction','ICU renovation — primary critical path task','high','2026-06-01','2026-06-15','2026-06-02','2026-06-18',14,17,75.00,85.00,'delayed','high',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0),
(2,2,'Electrical','OPD electrical and fit-out works','medium','2026-07-01','2026-07-10',NULL,NULL,9,0,0.00,0.00,'pending','medium',1,'2026-06-08 15:37:45',1,'2026-06-08 15:37:45',0);

/*Table structure for table `tbl_roles` */

DROP TABLE IF EXISTS `tbl_roles`;

CREATE TABLE `tbl_roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `role_name` varchar(100) NOT NULL,
  `role_description` text DEFAULT NULL,
  `permission_types` text DEFAULT NULL COMMENT 'Comma-separated list of permissions',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '1=Active, 2=Inactive',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=Not Deleted, 1=Deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_roles_role_name_unique` (`role_name`),
  KEY `tbl_roles_role_name_index` (`role_name`),
  KEY `tbl_roles_status_index` (`status`),
  KEY `tbl_roles_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_roles` */

insert  into `tbl_roles`(`id`,`role_name`,`role_description`,`permission_types`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'Super Admin','Full PDTS access including roles and settings','dashboard_view,dashboard_m1_kpis,dashboard_m1_chart_severity,dashboard_m1_chart_category,dashboard_m1_chart_project_status,dashboard_m1_chart_mitigation,dashboard_m1_chart_financial,dashboard_m1_chart_trend,dashboard_m1_chart_hospital,dashboard_m1_table_critical,roles,users,delay_categories,projects,delay_registers,mitigations,financial_impacts,delay_attachments,renovation_projects',1,1,'2026-06-08 12:47:29',1,'2026-06-10 19:54:02',0),
(2,'Admin','PDTS admin without role management','dashboard_view,users_creation,users_list,email_templates,settings,smtp_settings,razorpay_settings,send_push_notification,push_notifications_listing,projects,projects_list,projects_create,delay_registers,delay_registers_list,delay_registers_create,mitigations,mitigations_list,financial_impacts,financial_impacts_list,delay_attachments,ews_alerts,ews_config,renovation_projects,renovation_projects_list,renovation_projects_create,renovation_tasks,renovation_tasks_list,renovation_daily_logs,renovation_daily_logs_list,renovation_procurements,renovation_approvals,renovation_change_orders,renovation_costs,renovation_risks,executive_dashboard,delay_analytics,renovation_dashboard,audit_trail,delay_categories,delay_categories_list',1,1,'2026-06-08 12:47:29',1,'2026-06-08 16:26:20',0),
(3,'Manager','Manage delays, renovation projects, and reports','dashboard_view,users_list,projects,projects_list,projects_create,delay_registers,delay_registers_list,delay_registers_create,mitigations,mitigations_list,financial_impacts,financial_impacts_list,delay_attachments,ews_alerts,renovation_projects,renovation_projects_list,renovation_projects_create,renovation_tasks,renovation_tasks_list,renovation_daily_logs,renovation_daily_logs_list,renovation_procurements,renovation_approvals,renovation_change_orders,renovation_costs,renovation_risks,executive_dashboard,delay_analytics,renovation_dashboard,send_push_notification,push_notifications_listing',1,1,'2026-06-08 12:47:29',1,'2026-06-08 16:26:20',0),
(4,'Viewer','Read-only dashboards and listings','dashboard_view,projects_list,delay_registers_list,mitigations_list,financial_impacts_list,renovation_projects_list,renovation_tasks_list,renovation_daily_logs_list,executive_dashboard,delay_analytics,renovation_dashboard,delay_categories_list',1,1,'2026-06-08 12:47:29',1,'2026-06-08 16:26:20',0);

/*Table structure for table `tbl_root_causes` */

DROP TABLE IF EXISTS `tbl_root_causes`;

CREATE TABLE `tbl_root_causes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `cause_code` varchar(50) NOT NULL,
  `cause_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_root_causes_cause_code_unique` (`cause_code`),
  KEY `tbl_root_causes_status_index` (`status`),
  KEY `tbl_root_causes_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_root_causes` */

insert  into `tbl_root_causes`(`id`,`cause_code`,`cause_name`,`description`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'planning_gap','Planning Gap','Planning gap or incomplete upfront planning',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(2,'coordination_failure','Coordination Failure','Coordination failure between stakeholders',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(3,'vendor_underperformance','Vendor Underperformance','Vendor or contractor underperformance',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(4,'design_change','Design Change','Mid-project design or scope change',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(5,'approval_bottleneck','Approval Bottleneck','Approval or regulatory bottleneck',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0),
(6,'resource_constraint','Resource Constraint','Labor, material, or resource constraint',1,1,'2026-06-08 15:37:42',1,'2026-06-08 15:37:42',0);

/*Table structure for table `tbl_user` */

DROP TABLE IF EXISTS `tbl_user`;

CREATE TABLE `tbl_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `email_id` varchar(200) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `last_name` varchar(100) DEFAULT NULL,
  `mobile_no` varchar(20) DEFAULT NULL,
  `user_type` int(11) NOT NULL DEFAULT 1 COMMENT 'FK to tbl_roles.id',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '1=Active, 2=Inactive',
  `remember_token` varchar(100) DEFAULT NULL,
  `last_logged_on` datetime DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `reference_id` int(11) DEFAULT NULL COMMENT 'ID from the related registration table',
  `serial_number` varchar(50) DEFAULT NULL COMMENT 'Serial number from the registration table',
  `qr_code` varchar(255) DEFAULT NULL,
  `otp_code` varchar(10) DEFAULT NULL COMMENT 'OTP code for password reset',
  `otp_expiry` datetime DEFAULT NULL COMMENT 'OTP expiry date and time',
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0 COMMENT '0=Not Deleted, 1=Deleted',
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_user_username_unique` (`username`),
  KEY `tbl_user_username_index` (`username`),
  KEY `tbl_user_email_id_index` (`email_id`),
  KEY `tbl_user_user_type_index` (`user_type`),
  KEY `tbl_user_status_index` (`status`),
  KEY `tbl_user_mobile_no_index` (`mobile_no`),
  KEY `tbl_user_reference_id_index` (`reference_id`),
  KEY `tbl_user_serial_number_index` (`serial_number`),
  KEY `tbl_user_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_user` */

insert  into `tbl_user`(`id`,`username`,`email_id`,`password`,`first_name`,`last_name`,`mobile_no`,`user_type`,`status`,`remember_token`,`last_logged_on`,`profile_image`,`address`,`reference_id`,`serial_number`,`qr_code`,`otp_code`,`otp_expiry`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'admin','admin@pdts.com','$2y$10$b2fjwmZBSnzfzMeAUhJXzup2iuFXCIF1G/QIhDa0.ss32XUnMuABa','Admin','User',NULL,1,1,NULL,'2026-06-11 13:22:53',NULL,NULL,NULL,NULL,NULL,NULL,NULL,1,'2026-06-08 12:47:40',1,'2026-06-08 12:47:40',0);

/*Table structure for table `tbl_user_device_tokens` */

DROP TABLE IF EXISTS `tbl_user_device_tokens`;

CREATE TABLE `tbl_user_device_tokens` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned DEFAULT NULL,
  `device_token` varchar(500) NOT NULL,
  `device_id` varchar(255) DEFAULT NULL,
  `platform` varchar(20) NOT NULL DEFAULT 'android',
  `app_version` varchar(50) DEFAULT NULL,
  `status` tinyint(4) NOT NULL DEFAULT 1,
  `last_used_at` datetime DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IX_user_device_tokens_user_id` (`user_id`),
  KEY `IX_user_device_tokens_device_token` (`device_token`),
  KEY `IX_user_device_tokens_user_status` (`user_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_user_device_tokens` */

/*Table structure for table `tbl_zones` */

DROP TABLE IF EXISTS `tbl_zones`;

CREATE TABLE `tbl_zones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `zone_code` varchar(50) NOT NULL,
  `zone_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_on` datetime DEFAULT NULL,
  `updated_by` int(11) DEFAULT NULL,
  `updated_on` datetime DEFAULT NULL,
  `is_delete` tinyint(4) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `tbl_zones_zone_code_unique` (`zone_code`),
  KEY `tbl_zones_status_index` (`status`),
  KEY `tbl_zones_is_delete_index` (`is_delete`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

/*Data for the table `tbl_zones` */

insert  into `tbl_zones`(`id`,`zone_code`,`zone_name`,`description`,`status`,`created_by`,`created_on`,`updated_by`,`updated_on`,`is_delete`) values 
(1,'north','North Zone','Northern region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(2,'south','South Zone','Southern region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(3,'east','East Zone','Eastern region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(4,'west','West Zone','Western region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(5,'north_east','North East Zone','North eastern region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(6,'north_west','North West Zone','North western region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(7,'south_east','South East Zone','South eastern region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(8,'south_west','South West Zone','South western region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0),
(9,'central','Central Zone','Central region hospitals and projects',1,1,'2026-06-08 16:34:40',1,'2026-06-08 16:34:40',0);

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
