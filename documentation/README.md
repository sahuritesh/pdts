# PDTS — Project Delay Tracking System

Documentation for development, coding standards, and implementation planning.

## Documents

| File | Purpose |
|------|---------|
| [CODING_STANDARDS.md](CODING_STANDARDS.md) | Mandatory coding rules (ported from TDMS + PDTS-specific) |
| [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) | Step-by-step module build order and per-task checklist |
| [DATABASE_SCHEMA.md](DATABASE_SCHEMA.md) | Table reference aligned to FRS Modules 1–4 |
| [EXCEL_FRAMEWORK_ALIGNMENT.md](EXCEL_FRAMEWORK_ALIGNMENT.md) | Excel workbook mapping, seeders, deviations |

## Cursor AI Rules

Machine-readable rules live in `.cursor/rules/` and are applied automatically during development:

- `project-implementation-rules.mdc` — core workflow
- `ajax-pattern.mdc` — `ajaxRequestWithPromise` only
- `sidelayout-add-edit.mdc` — add/edit in offcanvas
- `image-path-utility.mdc` — `getImageUrl()` for files
- `required-field-indicator.mdc` — `required-label` class
- `migrations-location.mdc` — `database/migrations/ritesh/`
- `pdts-controller-response.mdc` — controllers, traits, permissions

**Before any implementation task:** read `.cursor/rules/` and the relevant section of `IMPLEMENTATION_PLAN.md`.

## Functional Requirements

Business requirements are defined in the FRS document (hospital renovation / delay tracking, Modules 1–4). Database tables are already migrated per FRS; application UI and business logic are built module by module per `IMPLEMENTATION_PLAN.md`.

## Reference Modules (copy patterns from)

| Module | Controller | Form View |
|--------|------------|-----------|
| Roles | `RoleManagement.php` | `roles/create-roles-form.blade.php` |
| Users | `UserManagement.php` | `users/create-users-form.blade.php` |
| Grid shell | — | `gridviews/gridviews.blade.php` |
| Layout | — | `layouts/template_v1.blade.php` |

## Environment

- Laravel 8, PHP via XAMPP: `d:\newxampp\php\php.exe artisan ...`
- Web-only admin panel (no mobile API)
- MySQL database `db_pdts`
