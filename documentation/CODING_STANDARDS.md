# PDTS Coding Standards

These rules are inherited from the TDMS project and apply to **every** PDTS task. Cursor rules in `.cursor/rules/` enforce the same standards during AI-assisted development.

---

## 1. Before You Code

1. Read applicable `.cursor/rules/*.mdc` files.
2. Read the task section in `documentation/IMPLEMENTATION_PLAN.md`.
3. Open an existing similar module (`RoleManagement`, `UserManagement`) and follow the same structure.
4. Do **not** introduce new frameworks, new AJAX libraries, or new UI patterns.

---

## 2. AJAX — Non-Negotiable

| Rule | Detail |
|------|--------|
| **Use** | `ajaxRequestWithPromise(url, data, postKey, isFormData, callback, buttonSelector, method)` |
| **Never use** | `$.ajax()`, `fetch()`, `axios`, raw `XMLHttpRequest` in views |
| **Loader** | Do not manually call `showGlobalLoader(true)` before requests — `ajaxRequestWithPromise` handles it |
| **Button state** | Pass submit button jQuery object as `buttonSelector` for automatic disable/spinner |
| **Errors** | `displayResponseMessage` runs automatically; use `parseFormErrors(res, 'error'|'success')` in `.then()` for form fields |

**Script include:** `assets/js/ajaxPromise.js` (loaded in `template_v1.blade.php`).

### FormData vs JSON

- File uploads: `isFormData = 1`, pass `FormData` object, append `_token`.
- Normal forms: `isFormData = 0`, serialize as `{ data: JSON.stringify(...) }` per existing controllers.

### GET requests

```javascript
ajaxRequestWithPromise(url, { id: id }, 'get_record', 0, '', null, 'GET')
```

---

## 3. UI — Sidelayout for Add/Edit

| Rule | Detail |
|------|--------|
| List page | `gridviews/gridviews.blade.php` + `$grid_data` from `buildGridConfig()` |
| Add button | `onclick="openSideLayout({}, url, title); return false;"` — not `<a href="...">` |
| Edit link | Generated in controller as `javascript:void(0)` + `openSideLayout` |
| After save | `closeSideLayout()` + reload DataTable |
| Select2 | `dropdownParent: $("#offcanvasRight")` |
| Form title | `$('.sidelayoutTitle').html('...')` in `$(document).ready()` |
| Submit | Button **click** handler — not `form onsubmit` |

**Utilities:** `openSideLayout`, `closeSideLayout`, `generateSideLayoutEditLink` in `assets/js/common.js`.

---

## 4. URL and Asset Helpers

| Helper | Use for |
|--------|---------|
| `getProjectUrl('path')` | Routes, links, AJAX URLs (subfolder-safe) |
| `getAssetUrl('images/...')` | CSS/JS/images under `/assets/` |
| `getImageUrl($dbPath)` | Files stored in DB (`uploads/...`) |

**Never** hardcode `http://localhost/pdts/...` in PHP or JavaScript.

---

## 5. File Uploads

- Write files with `public_path('uploads/...')` or configured upload path.
- Store in DB: `uploads/module_name/filename.ext` (no `public/` prefix).
- Display/download: `getImageUrl($row['file_path'])`.
- Validate type and size in controller before save.

---

## 6. Forms and Validation

### Required fields

```html
<label class="required-label" for="project_name">Project Name</label>
<input class="form-control required" name="project_name" id="project_name">
```

Never add manual `<span class="text-danger">*</span>`.

### Server validation

- Validate in controller private method (e.g. `validateProjectData()`).
- Return HTML `<li>` messages joined for `sendValidationErrorResponse()`.
- Check permissions with `permissionexists($this->module)` at start of every action.

---

## 7. Controllers

### Structure

```php
class ProjectsController extends Controller
{
    use GridConfigTrait, WebResponseTrait;

    public $module = 'projects';

    // index() or list view → grid
    // create/edit form → sidelayout-aware return
    // insert_update_*() → validate → Common_model → sendSuccessResponse()
    // get_*_list() → Datatables_model → JSON
}
```

### JSON responses (traits)

- `sendSuccessResponse($message, $operation, $redirectUrl)`
- `sendValidationErrorResponse($htmlMessage)`
- `sendErrorResponse($message, $errorCode)`
- `sendResponse($error, $message, $operation, $redirectUrl)`

### Data access

- Use `Common_model` and `Datatables_model` (existing pattern).
- Encrypt IDs in URLs: `Crypt::encrypt($id)` / `Crypt::decrypt($id)`.

---

## 8. Database

| Rule | Detail |
|------|--------|
| Migrations | Only in `database/migrations/ritesh/` |
| Naming | `tbl_` prefix |
| Audit | `created_by`, `created_on`, `updated_by`, `updated_on`, `is_delete` |
| Foreign keys | **None in DB** — integer refs + indexes only |
| Deletes | Soft delete (`is_delete = 1`), not `DELETE FROM` for business data |
| Calculations | Delay days, severity, risk, costs — compute in `app/Services/`, save result to column |

---

## 9. Permissions

When adding a module:

1. Add permission keys to `RolesSeeder.php`.
2. Add labels to `RoleManagement::getPermissionStructure()`.
3. Add table mapping in `Common_controller::hasTablePermission()` if grid status toggle applies.
4. Add sidebar menu item with `permissionexists()` check.
5. Set `public $module = 'permission_key'` on controller.

---

## 10. Routes

- Register in `routes/web.php` inside auth middleware group.
- Follow existing naming: `projects`, `projects/edit/{id}`, `insert_update_projects`, `get_projects_list`.
- No API routes — web-only application.

---

## 11. JavaScript Utilities (reuse, do not duplicate)

| File | Functions |
|------|-----------|
| `assets/js/ajaxPromise.js` | `ajaxRequestWithPromise`, `showGlobalLoader`, `displayResponseMessage` |
| `assets/js/common.js` | `openSideLayout`, `closeSideLayout`, `reloadDataTable`, `parseFormErrors`, status toggle handlers |
| `assets/js/custom.js` | Project-specific helpers (extend here if needed) |

---

## 12. What Not To Do

- Do not refactor unrelated modules while implementing one feature.
- Do not add Eloquent models unless the whole module team agrees (project uses `Common_model`).
- Do not add database foreign keys.
- Do not bypass permission checks.
- Do not create full-page add/edit when sidelayout pattern exists.
- Do not commit `.env` or credentials.

---

## 13. Per-Task Checklist

- [ ] Read cursor rules + implementation plan section
- [ ] Copied pattern from RoleManagement / UserManagement
- [ ] Routes + permissions + sidebar
- [ ] Grid with filters via `buildGridConfig`
- [ ] Sidelayout add/edit forms
- [ ] `ajaxRequestWithPromise` on all AJAX
- [ ] `required-label` on mandatory fields
- [ ] `getImageUrl` for uploads
- [ ] Service class for business calculations
- [ ] Tested add, edit, list, filters, permissions
