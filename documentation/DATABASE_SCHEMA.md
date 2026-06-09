# PDTS Database Schema Reference

Tables created per FRS. **No foreign key constraints** — relationships enforced in application code.

Run migrations: `d:\newxampp\php\php.exe artisan migrate`

---

## Excel framework masters

| Table | Purpose |
|-------|---------|
| `tbl_project_types` | Green Field, Brown Field, Renovation |
| `tbl_root_causes` | Planning Gap, Design Change, Approval Bottleneck, etc. |

See `EXCEL_FRAMEWORK_ALIGNMENT.md` for full mapping from `Project_Delay_Framework_Renovation_Enhanced.xlsx`.

---

## Module 1 — Project Delay Tracking

| Table | Purpose |
|-------|---------|
| `tbl_delay_categories` | Delay reason categories (10 buckets from Excel) |
| `tbl_projects` | Project master (+ `project_type_*`, `area_facility`, `project_spoc_name`, `target_revised_completion_date`) |
| `tbl_delay_registers` | Delay entries (+ `primary_delay_drivers`, `specific_event_description`, `impacted_task`, `root_cause_*`, `target_revised_completion_date`) |
| `tbl_delay_severity_rules` | Minor 1–7, Moderate 8–30, Critical &gt;30, Showstopper (licensing) |
| `tbl_delay_mitigations` | Mitigation action, owner, target date, status, remarks |
| `tbl_delay_financial_impacts` | Direct + opportunity cost line items |
| `tbl_delay_attachments` | Photos, drawings, NOCs, approvals, vendor comms, change orders |

### Key calculations (application layer)

```
delay_days = delay_end_date - delay_start_date
total_project_delay_cost = sum(direct) + sum(opportunity)
```

---

## Module 2 — Early Warning System

| Table | Purpose |
|-------|---------|
| `tbl_ews_alert_levels` | green, amber, red, black |
| `tbl_ews_escalation_matrix` | Levels 1–4 → roles (SPOC, Dept Head, Steering, Management) |
| `tbl_ews_prediction_config` | completion &lt; 50%, duration consumed &gt; 80% |
| `tbl_ews_potential_delay_alerts` | Generated alerts per task/project |

### EWS rule

```
IF task_completion_percent < 50 AND consumed_duration_percent > 80
THEN create potential delay alert
```

---

## Module 3 — Renovation Monitoring

| Table | Purpose |
|-------|---------|
| `tbl_renovation_projects` | Renovation project master |
| `tbl_renovation_tasks` | Tasks with priority, dates, completion %, duration % |
| `tbl_renovation_operational_impacts` | Shutdown, disruption score, relocation, infection clearance |
| `tbl_renovation_task_dependencies` | Dependency task + status |
| `tbl_renovation_risk_assessments` | Risk level from multiple delay factors |
| `tbl_renovation_procurements` | Vendor, status, material delay days |
| `tbl_renovation_approvals` | Approval status, pending days (auto) |
| `tbl_renovation_change_orders` | Count, description, approval status |
| `tbl_renovation_cost_tracking` | Budget, actual, cost overrun % |
| `tbl_renovation_daily_delay_logs` | Daily delay history |

### Key calculations (application layer)

```
cost_overrun_percent = ((actual_cost - budgeted_cost) / budgeted_cost) * 100
approval_pending_days = today - approval_submitted_date (when status = pending)
risk_level = f(delay_days, disruption_score, approval_delays, material_delays, dependency_delays)
```

---

## Module 4 — Audit

| Table | Purpose |
|-------|---------|
| `tbl_audit_trails` | created_by, created_on, modified_by, modified_on per entity change |

---

## Standard Columns (all business tables)

| Column | Type | Notes |
|--------|------|-------|
| `created_by` | int | `tbl_user.id` |
| `created_on` | datetime | |
| `updated_by` | int | |
| `updated_on` | datetime | |
| `is_delete` | tinyint | 0 = active, 1 = soft deleted |

---

## Reference Columns (no FK)

Use integer columns with comments documenting target table, e.g.:

- `project_id` → `tbl_projects.id`
- `delay_register_id` → `tbl_delay_registers.id`
- `renovation_project_id` → `tbl_renovation_projects.id`
- `owner_user_id` → `tbl_user.id`

Always filter `is_delete = 0` in listings.

---

## Seed Data

`PdtsMasterDataSeeder` provides:

- Delay categories (approval, material, contractor, etc.)
- Severity rules
- EWS alert levels and escalation matrix
- Prediction config defaults (50% / 80%)

`RolesSeeder` + `AdminUserSeeder` for login (`admin@pdts.local`).
