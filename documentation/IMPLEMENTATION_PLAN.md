# PDTS Implementation Plan

Step-by-step guide to build the application on top of the completed database schema. Follow `CODING_STANDARDS.md` for every task.

**Status:** Module 1 (Project Delay Tracking) complete — attachments added. Next: Phase 3 EWS or Phase 4 Renovation.

**Master / sample data source:** `documentation/Project_Delay_Framework_Renovation_Enhanced.xlsx` — see `EXCEL_FRAMEWORK_ALIGNMENT.md`.

---

## Phase 0 — Baseline (Done)

- [x] TDMS skeleton cleaned; PDTS branding applied
- [x] FRS-aligned tables created (`database/migrations/ritesh/`)
- [x] Master data seeded (categories, severity rules, EWS config, roles)
- [x] Coding rules defined (`.cursor/rules/`, `documentation/CODING_STANDARDS.md`)

---

## Phase 1 — Shared Infrastructure (Do First)

Build once; all modules depend on this.

### 1.1 Business Logic Services (`app/Services/`) — partial

| Service | Status |
|---------|--------|
| `DelayCalculationService` | Done |
| `DelaySeverityService` | Done |
| `EscalationService` | Done |
| `AuditTrailService` | Done |
| `DelayRegisterService` | Done (orchestrates calc + severity + escalation on save) |
| `FinancialImpactService` | Done (totals + project roll-up) |
| `EwsPredictionService` | Pending |
| `RenovationRiskService` | Pending |

### 1.2 Sidebar Navigation — partial (Delay Categories, Projects, Delay Register)

### 1.3 Audit Trail Integration — wired in Delay Categories, Projects, Delay Register save

---

## Phase 2 — Module 1: Project Delay Tracking

Build in this order (each item = controller + routes + grid + sidelayout form + permissions).

### 2.1 Delay Categories (Master) — Done

- Routes: `delay-categories-list`, sidelayout add/edit
- Controller: `DelayCategoriesController`

### 2.2 Project Master — Done

- Routes: `projects-list`, sidelayout add/edit
- Controller: `ProjectsController`
- Sample seeded projects visible in grid (Apollo Gurugram CONST-01/02)

### 2.3 Delay Register (Core) — Done

- **Table:** `tbl_delay_registers`
- **Controller:** `DelayRegistersController`
- **Routes:** `delay-registers-list`, sidelayout add/edit, `insert_update_delay_register`
- **On save:** `DelayRegisterService` → calc + severity + escalation; audit trail; project status → delayed
- **Grid columns:** project, title, category, days, severity, alert, escalation, status, dates
- **Filters:** search, project, category, hospital, severity, status
- **Detail tabs** (mitigations, financial, attachments): pending — see 2.4–2.6

### 2.4 Mitigation Tracking — Done

- **Table:** `tbl_delay_mitigations`
- **Controller:** `DelayMitigationsController`
- **Routes:** `delay-mitigations-list`, sidelayout add/edit, panel from delay register row
- **Fields:** action, owner, target date, status, remarks
- **UI:** Sidebar list + sidelayout panel per delay (shield icon on delay register grid)

### 2.5 Financial Impact — Done

- **Table:** `tbl_delay_financial_impacts`
- **Controller:** `DelayFinancialImpactsController`
- **Service:** `FinancialImpactService` — direct/opportunity totals + `tbl_projects.total_delay_cost` roll-up
- **One record per delay** (edit if exists)
- **UI:** Sidebar list + sidelayout summary panel (dollar icon on delay register grid)

### 2.6 Attachments — Done

- **Table:** `tbl_delay_attachments`
- **Controller:** `DelayAttachmentsController`
- **Upload:** FormData + `ajaxRequestWithPromise` (isFormData=1); files in `uploads/delay_attachments/`
- **Types:** photo, drawing, NOC, approval letter, vendor communication, change order, other
- **Multi-file:** one row per file; panel + list from delay register row

---

## Phase 2 — Module 1 complete

All delay-tracking CRUD (categories → projects → register → mitigations → financial → attachments) is implemented.

---

## Phase 3 — Module 2: Early Warning System (EWS) — Next

### 3.1 EWS Configuration (Admin)

- **Tables:** `tbl_ews_alert_levels`, `tbl_ews_escalation_matrix`, `tbl_ews_prediction_config`
- **Permission:** `ews_config`
- **UI:** Settings-style forms (already seeded — allow edit)

### 3.2 Potential Delay Alerts

- **Table:** `tbl_ews_potential_delay_alerts`
- **Permission:** `ews_alerts`
- **Trigger:** Run `EwsPredictionService` when renovation task progress/dates updated
- **Grid:** task, project, alert level, status, created date
- **Alert levels:** green, amber, red, black

### 3.3 Escalation Automation

- On severity change or alert creation → `EscalationService` sets level + queue notification (Phase 5)

---

## Phase 4 — Module 3: Renovation Project Monitoring

### 4.1 Renovation Project Master

- **Table:** `tbl_renovation_projects`
- **Fields:** project ID, name, scope, location, zone/department, renovation type
- **Pattern:** Same as `tbl_projects` CRUD

### 4.2 Task Tracking

- **Table:** `tbl_renovation_tasks`
- **Fields:** category, description, priority, planned/actual dates, completion %, duration %
- **On save:** trigger EWS check via `EwsPredictionService`
- **Grid:** filter by project, priority, status, risk

### 4.3 Operational Impact

- **Table:** `tbl_renovation_operational_impacts`
- **One row per renovation project:** shutdown, disruption score 1–10, relocation, infection clearance

### 4.4 Task Dependencies

- **Table:** `tbl_renovation_task_dependencies`
- **UI:** Highlight blocked tasks where dependency status ≠ complete
- **Grid badge:** "Blocked" when dependency pending

### 4.5 Risk Scoring

- **Table:** `tbl_renovation_risk_assessments`
- **On save:** `RenovationRiskService` → low/medium/high/critical

### 4.6 Procurement Tracking

- **Table:** `tbl_renovation_procurements`
- **Status flow:** pending → ordered → in_transit → delivered → installed
- **Field:** material_delay_days (manual or calculated)

### 4.7 Approval Tracking

- **Table:** `tbl_renovation_approvals`
- **Auto:** approval_pending_days from submitted date

### 4.8 Change Orders

- **Table:** `tbl_renovation_change_orders`

### 4.9 Cost Tracking

- **Table:** `tbl_renovation_cost_tracking`
- **Auto:** `cost_overrun_percent = ((actual - budget) / budget) * 100`

### 4.10 Daily Delay Log

- **Table:** `tbl_renovation_daily_delay_logs`
- **Fields:** date, reason, entered_by, corrective action
- **UI:** Grid + quick-add sidelayout; full history per project/task

---

## Phase 5 — Module 4: Dashboards, Reports & Notifications

### 5.1 Executive Dashboard — partial (Module 1 on main `/dashboard`)

- **KPIs on dashboard:** projects, open delays, critical count, total delay cost, mitigations, attachments
- **Charts:** severity, category, project status, mitigation status, 6-month trend, hospital breakdown
- **Service:** `DashboardAnalyticsService`
- **Remaining:** renovation KPIs, EWS alerts, dedicated `executive_dashboard` / `delay_analytics` report pages

### 5.2 Delay Analytics

- **Permission:** `delay_analytics`
- **Reports:** by category, month trend, hospital, contractor, responsibility, cost
- **Export:** reuse grid export pattern (CSV/Excel)

### 5.3 Renovation Dashboard

- **Permission:** `renovation_dashboard`
- **KPIs:** tasks completed/delayed, high risk, pending approvals, material delays, infection clearance pending

### 5.4 Notifications

- **Channels:** email (existing templates) + in-app (`tbl_notification_logs`)
- **Triggers:** new delay, escalation, approval pending &gt; X days, critical risk, missed completion date
- **Reuse:** Firebase/push infra already in project

### 5.5 Audit Trail UI

- **Permission:** `audit_trail`
- **Grid:** entity type, entity id, action, user, timestamp

---

## Phase 6 — UAT Before Go-Live

- [ ] Create project → log delay → verify delay_days and severity auto-calc
- [ ] Add mitigation, financial impact, attachments on delay
- [ ] Renovation project → tasks → dependency block highlight
- [ ] EWS alert fires when task &lt; 50% complete and &gt; 80% duration consumed
- [ ] Escalation level updates with severity
- [ ] Dashboard KPIs match underlying data
- [ ] Role restrictions on every route
- [ ] File upload/display works via `getImageUrl`
- [ ] All forms use sidelayout + `ajaxRequestWithPromise`

---

## Suggested Sprint Order

| Sprint | Deliverable |
|--------|-------------|
| 1 | Services + sidebar + Project Master + Delay Categories |
| 2 | Delay Register (core) + auto severity |
| 3 | Mitigation + Financial Impact + Attachments |
| 4 | Renovation Project + Tasks + Daily Delay Log |
| 5 | Operational Impact + Dependencies + Procurement + Approvals |
| 6 | Risk + Change Orders + Cost Tracking |
| 7 | EWS alerts + config UI |
| 8 | Dashboards + Analytics + Notifications + Audit UI |

---

## New Module Template (copy-paste workflow)

1. Create `app/Http/Controllers/XxxController.php` (copy `RoleManagement.php`)
2. Create `resources/views/xxx/create-xxx-form.blade.php` (copy roles form)
3. Add routes to `web.php`
4. Add permissions to `RolesSeeder`, `RoleManagement`, `hasTablePermission`
5. Add sidebar link
6. Create service methods if calculations needed
7. Test full CRUD cycle
