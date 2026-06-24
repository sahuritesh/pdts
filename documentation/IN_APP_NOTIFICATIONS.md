# In-App Notifications — Complete System Documentation (PDTS)

This document describes the **entire in-app notification system** used in PDTS: architecture, database, configuration, API, frontend behaviour, delay-notification integration, troubleshooting, and how to extend or port the module.

**Related (separate systems — not covered here in depth):**

| System | Purpose |
|--------|---------|
| `tbl_notification_logs` / `FirebaseNotificationService` | Push / Firebase mobile notifications |
| Email templates / `EmailTrait` | Email notifications |
| **This module** | Per-user **in-app** notifications (bell icon, badge, toasts, grid list) stored in `tbl_user_in_app_notifications` |

---

## Table of contents

1. [Overview](#1-overview)
2. [Architecture](#2-architecture)
3. [File & folder map](#3-file--folder-map)
4. [Database](#4-database)
5. [Configuration](#5-configuration)
6. [Installation in PDTS](#6-installation-in-pdts)
7. [HTTP routes & API](#7-http-routes--api)
8. [Backend services](#8-backend-services)
9. [PHP helpers](#9-php-helpers)
10. [Frontend UI](#10-frontend-ui)
11. [JavaScript polling & toasts](#11-javascript-polling--toasts)
12. [PDTS delay notifications](#12-pdts-delay-notifications)
13. [Click routing matrix (delay_logged)](#13-click-routing-matrix-delay_logged)
14. [EWS / severity (related context)](#14-ews--severity-related-context)
15. [My Notifications grid page](#15-my-notifications-grid-page)
16. [Security & permissions](#16-security--permissions)
17. [Adding a new notification type](#17-adding-a-new-notification-type)
18. [Porting to another Laravel project](#18-porting-to-another-laravel-project)
19. [Operations & maintenance](#19-operations--maintenance)
20. [Troubleshooting](#20-troubleshooting)
21. [Change log (implementation notes)](#21-change-log-implementation-notes)

---

## 1. Overview

### What it does

- Stores **one row per user per notification** in the database.
- Shows a **bell icon** in the admin header with an **unread count badge**.
- **Polls the server** every ~8 seconds (no page refresh) for new unread items.
- Shows **toastr popups** when genuinely new notifications arrive (once per notification per browser session).
- Dropdown shows up to **15 recent unread** items; **“View all”** opens a full **read + unread** grid.
- Clicking a notification **marks it read** and optionally **navigates** (redirect, sidelayout, or none).

### What it does not do (yet)

- Email delivery (planned separately for delay alerts).
- WebSocket / SSE real-time push (uses **polling** intentionally).
- Cross-user admin view of all notifications (each user sees only their own rows).

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         Browser (admin layout)                          │
│  header.blade.php → bell.blade.php                                      │
│  template_v1.blade.php → scripts.blade.php → in-app-notifications.js    │
└───────────────────────────────┬─────────────────────────────────────────┘
                                │ ajaxRequestWithPromise (GET/POST)
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│              InAppNotificationController (module)                        │
│   poll | markRead | index (grid) | getList (DataTables JSON)            │
└───────────────────────────────┬─────────────────────────────────────────┘
                                │
                                ▼
┌─────────────────────────────────────────────────────────────────────────┐
│              InAppNotificationService (module)                           │
│   notifyUser | notifyUsers | create | poll | markRead | unreadCount     │
└───────────────────────────────┬─────────────────────────────────────────┘
                                │
                                ▼
                    tbl_user_in_app_notifications

┌─────────────────────────────────────────────────────────────────────────┐
│  Domain layer (PDTS-specific, outside module)                            │
│  DelayNotificationService → notifyDelayLogged() on new delay register      │
│  Hook: ProjectWizardController::wizard_save_delay (Add only)             │
└─────────────────────────────────────────────────────────────────────────┘
```

**Design principle:** The module is **generic and portable**. Business rules (who gets notified, what URL to open) live in **domain services** like `DelayNotificationService`.

---

## 3. File & folder map

### Portable module

| Path | Role |
|------|------|
| `app/Modules/InAppNotifications/InAppNotificationsServiceProvider.php` | Registers config, routes, views, helpers, singleton service |
| `app/Modules/InAppNotifications/Services/InAppNotificationService.php` | Core CRUD, poll, mark read, user filtering |
| `app/Modules/InAppNotifications/Http/Controllers/InAppNotificationController.php` | HTTP endpoints + grid list |
| `app/Modules/InAppNotifications/helpers.php` | `in_app_notify()`, `in_app_notify_users()` |
| `app/Modules/InAppNotifications/config/in_app_notifications.php` | Default module config (merged with project config) |
| `app/Modules/InAppNotifications/resources/views/bell.blade.php` | Header bell + dropdown shell |
| `app/Modules/InAppNotifications/resources/views/scripts.blade.php` | JS config + script tag |
| `app/Modules/InAppNotifications/assets/js/in-app-notifications.js` | Polling, badge, toasts, click handling |
| `app/Modules/InAppNotifications/database/migrations/...` | Reference migration (copy to project) |
| `app/Modules/InAppNotifications/README.md` | Short porting guide |

### PDTS project integration

| Path | Role |
|------|------|
| `config/in_app_notifications.php` | Project-level config (overrides module defaults) |
| `config/app.php` | Registers `InAppNotificationsServiceProvider` |
| `composer.json` | Autoloads `app/Modules/InAppNotifications/helpers.php` |
| `database/migrations/ritesh/2026_06_18_120000_create_tbl_user_in_app_notifications.php` | Production migration |
| `assets/js/in-app-notifications.js` | Deployed JS (publish/sync from module) |
| `resources/views/common_pages/header.blade.php` | `@include('in-app-notifications::bell')` |
| `resources/views/layouts/template_v1.blade.php` | `@include('in-app-notifications::scripts')` after `ajaxPromise.js` |
| `app/Services/DelayNotificationService.php` | Delay logged → in-app notify |
| `app/Http/Controllers/ProjectWizardController.php` | Calls `notifyDelayLogged()` after successful delay **Add** |
| `config/delay_ews.php` | Human-readable EWS severity / alert / escalation definitions (delay UI legend) |
| `resources/views/project_wizard/partials/delay-ews-legend.blade.php` | Collapsible guide on delay panel |
| `database/seeders/TruncateTransactionalDataSeeder.php` | Truncates `tbl_user_in_app_notifications` in dev reset |

---

## 4. Database

### Table: `tbl_user_in_app_notifications`

Migration: `database/migrations/ritesh/2026_06_18_120000_create_tbl_user_in_app_notifications.php`

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint PK | Auto-increment notification id |
| `user_id` | int | **Recipient** (`tbl_user.id`) |
| `notification_type` | varchar(50) | App-defined type key, e.g. `delay_logged`, `general` |
| `title` | varchar(255) | Short title (bell, toast, grid) |
| `message` | text | Body text |
| `entity_type` | varchar(50) nullable | Domain entity key, e.g. `delay_register` |
| `entity_id` | int nullable | Domain entity id |
| `meta_json` | text nullable | JSON blob for extra context |
| `triggered_by` | int nullable | User who caused the notification |
| `action_url` | varchar(500) nullable | Relative app path on click |
| `action_mode` | varchar(20) | `redirect`, `sidelayout`, or `none` |
| `status` | tinyint | **0 = unread**, **1 = read** |
| `created_by`, `created_on` | audit | Set on insert (`current_datetime()`) |
| `updated_by`, `updated_on` | audit | Updated on mark read |
| `is_delete` | tinyint | Soft delete flag (0 = active) |

**Indexes:** `user_id`, `notification_type`, `entity_type`, `entity_id`, `status`, `is_delete`, composite `(user_id, status, is_delete)`.

**No foreign keys** — per PDTS standards; relationships enforced in application code.

### Status constants

Configured in `config/in_app_notifications.php`:

- `status_unread` => `0`
- `status_read` => `1`

---

## 5. Configuration

File: `config/in_app_notifications.php`

| Key | Default | Description |
|-----|---------|-------------|
| `table` | `tbl_user_in_app_notifications` | DB table name |
| `users_table` | `tbl_user` | Used to filter inactive recipients |
| `status_unread` / `status_read` | `0` / `1` | Read state values |
| `poll_interval_ms` | `8000` | Frontend poll interval (ms) |
| `poll_limit` | `15` | Max unread rows returned for bell dropdown |
| `routes.poll` | `in-app-notifications/poll` | GET poll endpoint |
| `routes.mark_read` | `in-app-notifications/mark-read` | POST mark read |
| `routes.list` | `in-app-notifications/list` | GET grid page |
| `routes.list_data` | `get_in_app_notification_list` | POST DataTables JSON |
| `middleware` | `web`, `Admin`, `SanitizePostData` | Route middleware (**`web` required** for session/auth) |
| `asset_version` | `1.3` | Cache-bust query string on JS |
| `js_global_config_key` | `inAppNotificationConfig` | `window` config object name |
| `active_user_status` | `ACTIVE` (1) | Only notify users with this status |

### Environment variables (optional)

```env
IN_APP_NOTIFICATIONS_TABLE=tbl_user_in_app_notifications
IN_APP_NOTIFICATIONS_USERS_TABLE=tbl_user
IN_APP_NOTIFICATIONS_POLL_MS=8000
IN_APP_NOTIFICATIONS_POLL_LIMIT=15
IN_APP_NOTIFICATIONS_ASSET_VERSION=1.3
```

### Timezone

Notification timestamps use `current_datetime()` → `config('app.timezone')` (PDTS: `Asia/Kolkata` via `APP_TIMEZONE`). Grid list uses `displayCustomDateTime()`.

---

## 6. Installation in PDTS

Already completed; checklist for verification:

1. **Migration run:** `tbl_user_in_app_notifications` exists.
2. **Service provider** in `config/app.php`:
   ```php
   App\Modules\InAppNotifications\InAppNotificationsServiceProvider::class,
   ```
3. **Composer autoload** includes `app/Modules/InAppNotifications/helpers.php`.
4. **Header:** `@include('in-app-notifications::bell')` in `resources/views/common_pages/header.blade.php`.
5. **Layout:** `@include('in-app-notifications::scripts')` in `resources/views/layouts/template_v1.blade.php` **after** `ajaxPromise.js`.
6. **Config** published at `config/in_app_notifications.php`.
7. **JS** at `assets/js/in-app-notifications.js` (module can publish to `public/assets/js/`).

---

## 7. HTTP routes & API

Registered by `InAppNotificationsServiceProvider` (not in `routes/web.php`).

All routes use middleware: `web`, `Admin`, `SanitizePostData`.

Full URLs use `getProjectUrl()` — typically `{APP_URL}/admin/in-app-notifications/...`.

### 7.1 `GET in-app-notifications/poll`

**Purpose:** Bell badge, dropdown data, live new-item detection.

**Query parameters:**

| Param | Required | Description |
|-------|----------|-------------|
| `since_id` | No | If **present** (even `0`), enables **incremental** mode: `new_items` = unread rows with `id > since_id`. If **absent**, `new_items` is empty (bootstrap / dropdown refresh). |

**Response JSON:**

```json
{
  "error": 0,
  "unread_count": 3,
  "latest_id": 42,
  "notifications": [ { "id", "type", "title", "message", "action_url", "action_mode", "entity_type", "entity_id", "created_on" } ],
  "new_items": [ /* same shape; only when since_id sent */ ]
}
```

**Errors:** `error: 1` unauthorized, `error: 2` server error.

### 7.2 `POST in-app-notifications/mark-read`

**Purpose:** Mark one notification read for the logged-in user.

**Body:**

```json
{ "notification_id": 42 }
```

Or wrapped form style: `data` JSON with `notification_id` (compatible with `ajaxRequestWithPromise`).

**Response:**

```json
{ "error": 0, "unread_count": 2 }
```

### 7.3 `GET in-app-notifications/list`

**Purpose:** Full **My Notifications** grid page (`gridviews.gridviews`).

Columns: `#`, Title, Message, Type, Status, Received On.

Filters: search (title/message/type), status (Unread / Read / All).

### 7.4 `POST get_in_app_notification_list`

**Purpose:** DataTables server-side JSON for the grid. Scoped to `Auth::id()` only.

---

## 8. Backend services

### 8.1 `InAppNotificationService`

| Method | Description |
|--------|-------------|
| `notifyUser($userId, $type, $title, $message, $options)` | Insert one notification; returns new `id` or `null` |
| `notifyUsers($userIds, ...)` | Batch; returns array of created ids |
| `create($data)` | Low-level insert |
| `unreadCount($userId)` | Count unread for badge |
| `recentUnread($userId, $limit)` | Latest unread for dropdown (default limit from config) |
| `poll($userId, $sinceId, $incremental)` | Builds poll API payload |
| `markRead($notificationId, $userId)` | Sets `status = 1`; only if row belongs to user |
| `filterActiveUserIds($userIds, $excludeUserId)` | Removes inactive/deleted users; dedupes |

#### `$options` when creating

| Key | Description |
|-----|-------------|
| `action_url` | Relative path, e.g. `projects/wizard/{enc}?step=execution` |
| `action_mode` | `redirect` (default), `sidelayout`, `none` |
| `entity_type` | e.g. `delay_register` |
| `entity_id` | int |
| `meta` or `meta_json` | array or JSON string |
| `triggered_by` | user id (defaults to `Auth::id()`) |

### 8.2 `DelayNotificationService` (PDTS domain)

| Method | Description |
|--------|-------------|
| `notifyDelayLogged($delayRegisterId, $loggedByUserId)` | Loads delay + project; notifies recipients |
| `resolveRecipientUserIds($projectId, $excludeUserId)` | Project SPOC + all dept SPOCs on project |

**Trigger:** `ProjectWizardController::wizard_save_delay` — **only on Add** (not Update), after successful insert and audit log.

**Excluded recipient:** User who logged the delay (`excludeUserId`).

**Notification type:** `delay_logged`

**Title format:** `Delay: {Department} — {project_code} — {project_name}`

**Message format:** `{delay_title} ({Severity}, {N} days) — logged by {name}`

**meta_json example:**

```json
{
  "project_id": 1,
  "project_department_id": 5,
  "delay_register_id": 12
}
```

---

## 9. PHP helpers

Loaded via Composer + service provider.

```php
// Single user
$id = in_app_notify($userId, 'task_assigned', 'Title', 'Message body', [
    'action_url' => 'spoc-tasks/view/' . Crypt::encrypt($pdId),
    'action_mode' => 'sidelayout',
    'entity_type' => 'project_department',
    'entity_id' => $pdId,
    'triggered_by' => Auth::id(),
]);

// Multiple users
$ids = in_app_notify_users([2, 5, 9], 'general', 'Title', 'Message', [
    'action_url' => 'in-app-notifications/list',
    'action_mode' => 'redirect',
]);
```

---

## 10. Frontend UI

### Bell (`bell.blade.php`)

- Button class: `in-app-notification-bell`
- Badge id: `#inAppNotifyCount` (hidden when 0)
- Dropdown id: `#inAppNotifyDropdown`
- List container: `#inAppNotifyList`
- Link: **View all** → `in-app-notifications/list`

### Scripts (`scripts.blade.php`)

Exposes `window.inAppNotificationConfig`:

```javascript
{
  pollUrl: ".../in-app-notifications/poll",
  markReadUrl: ".../in-app-notifications/mark-read",
  pollIntervalMs: 8000
}
```

Loads `assets/js/in-app-notifications.js?v={asset_version}`.

### Dependencies

- jQuery
- `ajaxRequestWithPromise` from `assets/js/ajaxPromise.js` (9th param `skipLoader=true` for silent polls)
- Bootstrap 5 dropdown
- **toastr** (optional but required for popups)
- **openSideLayout** from `common.js` (for `action_mode: sidelayout`)

---

## 11. JavaScript polling & toasts

File: `assets/js/in-app-notifications.js`

### State machine

| Phase | Request | Toasts? | Updates |
|-------|---------|---------|---------|
| **Bootstrap** (once on load) | `GET poll` **without** `since_id` | No — existing unread marked as “seen” | `latestId`, badge |
| **Incremental poll** (every 8s) | `GET poll?since_id={latestId}` | Yes — for each item in `new_items` not in `toastedIds` | badge, `latestId` |
| **Bell click** | `GET poll` without `since_id` | No | Renders dropdown list only |
| **Click notification** | `POST mark-read` | No | Navigate per `action_mode` |
| **Tab visible again** | Incremental poll | Per new items only | badge |

### Duplicate toast prevention

- `state.toastedIds` — object keyed by notification id; each id toasts **at most once** per page session.
- Bootstrap marks all existing unread as toasted without showing them.
- Bell open does **not** toast.
- Click does **not** toast (navigate only).

### `action_mode` behaviour on click

| Mode | Behaviour |
|------|-----------|
| `redirect` | `window.location.href = baseURL + action_url` |
| `sidelayout` | `openSideLayout({}, actionUrl, title)` |
| `none` | Mark read only; no navigation |

### Init guard

`window.__inAppNotifyInitialized` prevents double script init (duplicate timers).

---

## 12. PDTS delay notifications

### End-to-end flow

```
User saves new delay (wizard delay panel)
    → POST wizard_save_delay
    → ProjectWizardController validates department access
    → Duplicate guard (same title/dept/user within 15s)
    → Insert tbl_delay_registers
    → setDepartmentDelay()
    → auditTrail log
    → DelayNotificationService::notifyDelayLogged()
        → For each recipient: InAppNotificationService::notifyUser()
    → Success JSON to browser

Other users' browsers (within ~8s)
    → poll?since_id=N
    → new_items contains notification
    → toastr popup + badge increment
```

### Duplicate delay register guard

Server-side in `wizard_save_delay`: rejects duplicate insert same `project_department_id` + `delay_title` + `created_by` within **15 seconds** (returns success without second insert).

Client-side: `window.__wizardDelayHandlersBound` + `__wizardDelaySaving` on delay panel JS.

---

## 13. Click routing matrix (`delay_logged`)

Per-recipient logic in `DelayNotificationService::buildActionForRecipient()`:

| Recipient | `action_url` | `action_mode` |
|-----------|--------------|---------------|
| **Project SPOC** (`tbl_projects.responsible_user_id`) | `projects/wizard/{encProjectId}?step=execution` | `redirect` |
| **SPOC of the delayed department** | `spoc-tasks/view/{encDelayedPdId}` | `sidelayout` |
| **Other department SPOCs** on same project (e.g. MEP when Civil delayed) | `in-app-notifications/list` | `redirect` |

**Rationale for other dept SPOCs:** They cannot open another department’s task; sending them to their own pending department task was confusing. They are directed to **My Notifications** to read the full message.

**Note:** Notifications created **before** routing fixes may still have old `action_url` values stored in DB until new delays are logged.

---

## 14. EWS / severity (related context)

Delay notifications include **severity** in the message text. Severity, alert level, and escalation are auto-calculated by `DelayRegisterService` on save (not part of the notification module, but shown on delay panel).

Definitions: `config/delay_ews.php`  
UI legend: `resources/views/project_wizard/partials/delay-ews-legend.blade.php`  
Helpers: `delayEwsDefinition()`, `delayEwsLabel()` in `common_helper.php`

| Severity | Typical days | Alert | Escalation |
|----------|--------------|-------|------------|
| Minor | 1–7 | Green | Level 1 — Project SPOC |
| Moderate | 8–30 | Amber | Level 2 — Department Head |
| Critical | >30 | Red | Level 3 — Steering Committee |
| Showstopper | Licensing/opening | Black | Level 4 — Management |

---

## 15. My Notifications grid page

- **URL:** `getProjectUrl('in-app-notifications/list')`
- **Controller:** `InAppNotificationController::index`
- **Data:** `get_in_app_notification_list` (POST)
- **Scope:** Current user only (`tb.user_id = Auth::id()`)
- **Filters:** Search, Unread/Read status
- **Timestamps:** `displayCustomDateTime()` (IST when `APP_TIMEZONE=Asia/Kolkata`)

Bell dropdown shows **unread only** (max 15). Grid shows **read + unread** history.

---

## 16. Security & permissions

- All routes require authenticated admin session (`web` + `Admin` middleware).
- Poll / mark-read / grid data filtered by `Auth::id()` — users cannot read others’ notifications.
- `markRead` updates only rows where `user_id` matches current user.
- `SanitizePostData` middleware on routes.
- No DB foreign keys; `user_id` must reference valid `tbl_user.id` by application discipline.

---

## 17. Adding a new notification type

1. **Create a domain service** (e.g. `TaskAssignmentNotificationService`) — do not put business rules inside the module.
2. Resolve recipient user ids and per-user `action_url` / `action_mode`.
3. Call `in_app_notify()` or `in_app_notify_users()` with a distinct `notification_type` string.
4. Hook from the appropriate controller **after** successful DB commit.
5. Optionally add grid filter labels (type column auto-formats `snake_case` → words).
6. Document the new type in this file.

**Example hook pattern:**

```php
$this->myNotificationService->notifySomething($entityId, (int) Auth::id());
```

---

## 18. Porting to another Laravel project

See also `app/Modules/InAppNotifications/README.md`.

1. Copy `app/Modules/InAppNotifications/` entire folder.
2. Copy `config/in_app_notifications.php`.
3. Copy migration to your migrations folder; run `php artisan migrate`.
4. Copy `assets/js/in-app-notifications.js`.
5. Register service provider in `config/app.php`.
6. Add helpers to `composer.json` autoload files; `composer dump-autoload`.
7. Include bell + scripts in layout.
8. Ensure `ajaxRequestWithPromise`, toastr, and `getProjectUrl` equivalents exist.
9. Set `middleware` to include `web` + your auth middleware.
10. Implement domain notifiers per feature.

---

## 19. Operations & maintenance

### Run migration

```bash
php artisan migrate
```

Migration path in PDTS: `database/migrations/ritesh/2026_06_18_120000_create_tbl_user_in_app_notifications.php`

### Clear config cache after .env changes

```bash
php artisan config:clear
```

### Truncate notifications (dev)

`TruncateTransactionalDataSeeder` includes `tbl_user_in_app_notifications`.

### Bump JS cache

Update `IN_APP_NOTIFICATIONS_ASSET_VERSION` or `asset_version` in config.

### Publish module assets (optional)

```bash
php artisan vendor:publish --tag=in-app-notifications-config
php artisan vendor:publish --tag=in-app-notifications-assets
```

---

## 20. Troubleshooting

| Symptom | Likely cause | Fix |
|---------|--------------|-----|
| Poll redirects to login/dashboard loop | Missing `web` middleware on routes | Ensure `web` in `config/in_app_notifications.php` middleware array |
| Badge never updates | JS not loaded or `#inAppNotifyCount` missing | Check `scripts.blade.php` include and asset path |
| No toasts on new notification | Old JS cached; poll not incremental | Hard refresh; verify `since_id` on poll after bootstrap |
| Toasts repeat on bell click | Old JS before v1.3 fix | Use asset v1.3+; bell must not pass `since_id` for toasts |
| Toast only after bell click | `latestId` stuck at 0 / no incremental poll | Fixed in v1.3 — bootstrap then `since_id=0` incremental |
| Click opens wrong department task | Old `action_url` in DB | Log new delay after routing fix; or update rows manually |
| MEP SPOC sees Civil as MEP task | Old routing to own `spoc-tasks/view` | Current routing sends to notifications list |
| Timestamps wrong timezone | `APP_TIMEZONE` / config cache | Set `APP_TIMEZONE=Asia/Kolkata`; `config:clear` |
| Duplicate delay rows | Double submit | Server 15s duplicate guard + client `__wizardDelaySaving` |
| `in_app_notify` undefined | Helpers not autoloaded | `composer dump-autoload` |

### Debug poll manually

Browser network tab: `GET .../in-app-notifications/poll?since_id=N` should return `error: 0` and JSON (not HTML redirect).

---

## 21. Change log (implementation notes)

| Date / area | Change |
|-------------|--------|
| Module created | Portable `InAppNotifications` module with poll + mark-read |
| PDTS integration | Bell in header; delay hook via `DelayNotificationService` |
| Routes fix | Added `web` middleware (session/auth for poll) |
| List page | `in-app-notifications/list` grid with read/unread filter |
| Delay routing | Per-recipient URLs; cross-dept → notifications list |
| Toast fixes | Bootstrap vs incremental poll; `toastedIds`; no toast on bell |
| Timezone | `Asia/Kolkata`; `displayCustomDateTime` with explicit timezone |
| EWS UI | `config/delay_ews.php` + legend on delay panel |
| Duplicate delay | 15s server guard + single document-level save handler |

---

## Quick reference — key URLs (relative)

| Page / endpoint | Path |
|-----------------|------|
| Poll | `in-app-notifications/poll` |
| Mark read | `in-app-notifications/mark-read` |
| My Notifications | `in-app-notifications/list` |
| Grid data | `get_in_app_notification_list` |

---

*Last updated: PDTS in-app notification system as implemented with asset version 1.3 and delay notification integration.*
