# Excel Framework Alignment

Source: `documentation/Project_Delay_Framework_Renovation_Enhanced.xlsx`

Sheets:
- **Sheet1** — Delay register column template + category examples
- **Construction** — Construction project master + delay rows (Apollo Gurugram samples)
- **Renovation Projects** — Renovation master + task/operational/procurement fields in one row

---

## What Was Seeded

### Masters (`PdtsMasterDataSeeder`)

| Master | Excel source | Table |
|--------|--------------|-------|
| Project types | Green Field, Brown Field, Renovation | `tbl_project_types` |
| Root causes | Planning Gap, Coordination Failure, etc. | `tbl_root_causes` |
| Delay categories | 10 category buckets from Construction row 2 | `tbl_delay_categories` |
| Severity rules | Minor 1–7, Moderate 8–30, Critical >30, Showstopper | `tbl_delay_severity_rules` |
| EWS config | 50% completion / 80% duration | `tbl_ews_prediction_config` |

Legacy generic delay categories from the first seed are **deactivated** (`is_delete = 1`).

### Sample data (`PdtsFrameworkSampleDataSeeder`)

| Excel row | Seeded as |
|-----------|-----------|
| AH-Gurugram - CONST-01 | `tbl_projects` + delay register (Showstopper) |
| AH-Gurugram - CONST-02 | `tbl_projects` + delay (17 days, Moderate) + mitigation |
| REN-001 ICU Renovation | Full renovation stack (task, impact, dependency, risk, procurement, approval, change orders, cost, daily log) |
| REN-002 OPD Upgrade | Renovation project + related records |

Run: `d:\newxampp\php\php.exe artisan db:seed --class=PdtsMasterDataSeeder --force`  
Then: `d:\newxampp\php\php.exe artisan db:seed --class=PdtsFrameworkSampleDataSeeder --force`

---

## Schema Additions (migration `2026_06_09_103000`)

| Table | New columns | Excel column |
|-------|-------------|--------------|
| `tbl_projects` | `project_type_id`, `project_type_label`, `area_facility`, `project_spoc_name`, `target_revised_completion_date` | Project Type, Area/Facility, SPOC, Target Revised Completion |
| `tbl_delay_registers` | `primary_delay_drivers`, `specific_event_description`, `impacted_task`, `root_cause_id`, `root_cause_label`, `target_revised_completion_date` | Primary Delay Drivers, Specific Event, Impacted Task, Root Cause, Target Revised Date |
| `tbl_renovation_projects` | `final_handover_date`, `escalation_status`, `remarks` | Final Handover, Escalation Status, Remarks |
| `tbl_renovation_risk_assessments` | `risk_score` (1–10) | Risk Score |
| **New** `tbl_project_types` | — | Project Type master |
| **New** `tbl_root_causes` | — | Root Cause master |

---

## Alignments (Excel matches FRS / schema)

| Requirement | Excel | PDTS |
|-------------|-------|------|
| Delay days | End − Start | `tbl_delay_registers.delay_days` |
| Severity bands | Minor/Moderate/Critical/Showstopper | `tbl_delay_severity_rules` |
| EWS rule | 50% progress, 80% time | `tbl_ews_prediction_config` |
| Mitigation | Mitigation Action Taken | `tbl_delay_mitigations` |
| Direct / Opportunity cost | Separate columns | `tbl_delay_financial_impacts` |
| Renovation fields | Single wide row | Normalized across `tbl_renovation_*` tables |
| Infection clearance | Approved/Pending | `tbl_renovation_operational_impacts` |
| Procurement status | Pending/Ordered/… | `tbl_renovation_procurements` |
| Cost overrun % | 0.15 → 15% | Stored as `15.00` percent |

---

## Deviations / Notes

| Topic | Excel | PDTS approach | Action |
|-------|-------|---------------|--------|
| **Risk score** | Numeric 8, 5 | FRS uses Low/Medium/High/Critical bands | Added `risk_score` column; map score → band in `RenovationRiskService` |
| **Escalation** | "Escalated" / "None" text | FRS uses escalation levels 1–4 | `escalation_status` on renovation project; delay register uses `escalation_level` |
| **Change orders** | Count "2" | One row per change order | Two `tbl_renovation_change_orders` rows for REN-001 |
| **Dependency task** | Label only ("Design Approval") | Schema expects `dependency_task_id` | Use `notes` for label when prerequisite task not in system; `dependency_task_id = 0` |
| **Before/after pics** | Column on register | Attachment types | Use `tbl_delay_attachments` with type `photo_before` / `photo_after` (UI pending) |
| **Project document** | Attachment column | `tbl_delay_attachments` | type `project_document` |
| **Template rows** | Rows 3–10 without hospital | Category examples only | Categories seeded; projects not created for placeholder IDs |
| **Sheet1 vs Construction** | Slightly different column order | Construction sheet is authoritative for construction projects | Implementation uses Construction tab |
| **Responsibility** | Contractor, Architect, Hospital Admin, Vendor | Free-text `responsibility_name` | Optional future master `tbl_responsibility_types` |
| **Mitigation status** | "Status" column (sparse in Excel) | open/in_progress/escalated/closed | FRS status values on `tbl_delay_mitigations` |

---

## Not in Excel (FRS only — no change needed)

- Audit trail (`tbl_audit_trails`)
- Separate EWS alert records (`tbl_ews_potential_delay_alerts`)
- Escalation matrix master (`tbl_ews_escalation_matrix`)
- Email / in-app notifications

---

## Re-parse Excel (maintenance)

```bash
d:\newxampp\php\php.exe tools/parse_excel.php
```

Output: `tools/excel_dump.json`
