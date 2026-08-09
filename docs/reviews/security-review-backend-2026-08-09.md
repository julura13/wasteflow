# Backend Security Review — 2026-08-09

Scope: whole-codebase security review of the backend (`app/Http/Controllers`, `app/Http/Middleware`, `app/Http/Requests`, `app/Models`, `app/Jobs`, `app/Services`, `app/Console/Commands`, `routes/web.php`, `database/migrations`). Frontend, tests, and documentation were out of scope. Focus: multi-tenant isolation (company/branch/site scoping), authorization, injection, mass assignment, and insecure direct object references.

## Vuln 1: Broken Access Control (IDOR): `app/Http/Controllers/MediaController.php:102`

* Severity: High
* Description: `MediaController::download(Media $media)` streams any media file by its plain auto-increment ID with no check that the file's parent `Order` belongs to the requesting user's company. The `/media/{media}/download` route (`routes/web.php:113`) is gated only by a broad `permission:{$ordersPermission}` check (any of `orders-view`, `orders-capture-documents`, `orders-capture-weights`, etc.) — permissions held by company-scoped operational roles like `weights_capture` and `document_capture`. `UserController::store`/`update` allow `company_id` and `roles` to be set completely independently, so nothing prevents assigning e.g. `weights_capture` to a user who is also scoped to a specific client company (`scopedCompanyIdsForUser()` / `isClientScoped()` in `ScopeByClientTrait.php:17-25` key off exactly that combination).
* Exploit Scenario: A user with the `weights_capture` role and `company_id` set to Company A (e.g. that company's own weighbridge operator, a legitimate account configuration the app supports) requests `GET /media/1/download`, `/media/2/download`, etc., incrementing through IDs. Every media record belongs to *some* order regardless of company, so this enumerates and downloads scale slips, manifests, and other order attachments belonging to Company B, C, and every other tenant — a straightforward cross-tenant data breach requiring no special tooling.
* Recommendation: Load `$media->mediable` (the `Order`) and re-run the same company-scope check `OrderController` already applies via `ensureOrderInScope()` before serving the file.
* Status: **Fixed** — see commit following this review.

## Vuln 2: Broken Access Control (IDOR - delete): `app/Http/Controllers/MediaController.php:116`

* Severity: High
* Description: `MediaController::destroy(Media $media)` deletes the file (both cloud and local-cache copies) and the DB record for any media ID, with the same missing-ownership-check pattern as the download endpoint above. Route: `DELETE /media/{media}` (`routes/web.php:114`), same `permission:{$ordersPermission}` gate.
* Exploit Scenario: The same company-scoped `weights_capture`/`document_capture` user from Vuln 1 sends `DELETE /media/{id}` for media IDs belonging to other companies' orders, permanently destroying another tenant's collection slips/manifests/supporting documents — destructive cross-tenant sabotage, not just a read leak.
* Recommendation: Same fix as Vuln 1 — verify the media's parent order is within the acting user's scope before allowing deletion.
* Status: **Fixed** — see commit following this review.

## Vuln 3: Broken Access Control (IDOR - write): `app/Http/Controllers/MediaController.php:19`

* Severity: Medium
* Description: `MediaController::upload()` validates `mediable_id` only as `exists:orders,id` and then does `Order::findOrFail($validated['mediable_id'])` with no check that the order belongs to the uploading user's company.
* Exploit Scenario: The same company-scoped user submits `POST /media/upload` with `mediable_id` set to an order ID belonging to a different company, planting an arbitrary file (up to 10MB, any type passing basic `file` validation) onto another tenant's order — e.g. to inject misleading documents into another company's order history.
* Recommendation: Same scope check as above, applied before `Order::findOrFail()` is used to build the storage path.
* Status: **Fixed** — see commit following this review.

---

## Investigated, not reported (no concrete exploit found)

- **SQL injection**: no raw SQL (`DB::raw`, `whereRaw`, `selectRaw`, `DB::statement`) with interpolated user input anywhere in `app/`.
- **Mass assignment**: all models use explicit `$fillable` lists populated from validated request data, not `$request->all()`.
- **`OrderController`**: `show`/`update`/`editCollectionDate`/`updateCollectionDate`/`updateStatus`/`saveWeights`/`finalize`/`downloadPDF` all correctly call `ensureOrderInScope()`/`canManageOrdersForCompany()`; export/report download endpoints all re-check `user_id` ownership plus expiry before streaming.
- **`ReportController`**: all report/PDF endpoints route through `ScopeByClientTrait::enforceCompanyScope()`. The unauthenticated print-preview route (`/reports/resource-intelligence/print/{token}`) is gated by a one-time, 10-minute-TTL v4 UUID cache token — not practically guessable.
- **Company/settings management controllers** (`CompanyController`, `BranchController`, `SiteController`, `MaterialController`, `ServiceProviderController`, `RoleController`, `Settings/*`): all gated by permissions (`manage-clients`, `manage-services`, `manage-roles`, `manage-settings`) that per the seeder are only ever granted to roles that also hold `view-reports-all` — not a cross-tenant issue.
- **`ActivityLogController`**: looks up orders by tracking number with no scoping, but `view-activity-log` is only granted to internal staff roles that already have `view-reports-all`.
- **`Document`/`DocumentController`**: intentionally a shared/global repository (viewable by all authenticated users per design), not tenant-scoped — not a bug.

## Design note (not a finding — no reachable exploit path today)

`ensureOrderInScope()` (in `OrderController`) keys off the legacy `users.company_id` column, while `canManageOrdersForCompany()`/`getRoleForCompany()` key off the `company_user` pivot table. These are inconsistent, but every code path that could populate the pivot table (`CompanyUserService::assignUserToCompany`, `AssignCompanyUserRequest`) was traced and found to have **no controller or route wiring it up** — there is currently no reachable way to create a pivot-only (no `company_id`) company user through the application. Worth cleaning up if/when pivot-based company assignment is wired up, but not exploitable as-is.
