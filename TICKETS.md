# Tickets (Top Missing Items)

This list converts the current gaps into actionable work. Each ticket includes scope and acceptance criteria.

## 1. Client Portal Experience (Separate UX)

**Problem**: Client users currently use the staff dashboard/reports with company-level scoping only. There is no distinct client portal or tailored UX.

**Scope**
- Add dedicated client-facing pages (dashboard, reports, downloads).
- Provide a client navigation shell distinct from staff UX.
- Ensure visual/UX alignment with client usage patterns (summary-first, fewer admin actions).

**Acceptance Criteria**
- Client role lands on a client-only dashboard (not the staff dashboard).
- Client navigation hides staff-only modules (companies, services, settings, roles, users).
- Client dashboard loads and renders without 403 errors for client users.
- Client report pages are accessible from client nav and scoped to client data.


## 2. Client Site Manager Scoping (Branch/Site-level Access)

**Problem**: Client access is scoped only by `company_id`. There is no branch/site scoping for site managers.

**Scope**
- Add branch/site scoping to client users.
- Enforce scope in dashboards, reports, and order visibility.
- Add role/permission checks if needed for site-level constraints.

**Acceptance Criteria**
- A client user assigned to a site only sees data for that site.
- A client user assigned to a branch sees data for all sites within that branch.
- A client user assigned only to a company sees company-level data.
- Filtering endpoints (branches/sites) do not reveal out-of-scope data.


## 3. CSV/Excel Export for Reports

**Problem**: Only PDF export exists. CSV/Excel export is missing.

**Scope**
- Add CSV export for waste management report data.
- Add Excel export (XLSX) for the same datasets.
- Wire export actions to the Reports UI.

**Acceptance Criteria**
- Export buttons appear on report pages.
- CSV export downloads include headers and all rows shown in the report view.
- Excel export downloads mirror CSV content and open in Excel without warnings.
- Access control enforced (clients only export their scoped data).


## 4. Email Notifications for Order Lifecycle

**Problem**: No order notifications or templates are implemented.

**Scope**
- Notify service providers on new orders.
- Notify clients on order confirmation and finalization.
- Add mail templates and queue configuration.

**Acceptance Criteria**
- On order creation, a service provider email is sent with order details.
- On order finalization, a client email is sent with key order summary.
- Templates are stored in the codebase and are configurable.
- Email dispatch uses the queue if configured.


## 5. Audit Trail & Login Activity Tracking

**Problem**: Only partial order status history exists; user activity and login tracking are missing.

**Scope**
- Track login timestamp and login count on users.
- Add audit logging for critical actions (create/update/delete on key models).
- Provide an admin UI for viewing activity logs.

**Acceptance Criteria**
- User model stores `last_login_at` and `login_count` (or equivalent).
- Login events update those fields reliably.
- Create/update/delete actions for orders, companies, branches, sites, materials, and users are logged.
- Admin UI displays audit entries with filter/search.


## 6. Order Grouping / Root Order Number

**Problem**: No root order grouping exists.

**Scope**
- Implement root order number fields and grouping logic.
- UI to view grouped orders and their child numbers.

**Acceptance Criteria**
- Orders created from the same client batch are grouped with a root order number.
- Child orders show their root link (e.g., 33.1, 33.2).
- Order show page displays group association.


## 7. Advanced Order Filters

**Problem**: Orders index only supports text search + status.

**Scope**
- Add filters for company, branch, site, service provider, and date range.
- Persist filters in query string.

**Acceptance Criteria**
- Filters update the orders list without page errors.
- Filters are reflected in the URL.
- Role-based access is respected for filter options.


## 8. Soft Deletes & Historical Preservation

**Problem**: is_active flags exist, but no soft delete rules or historical retention.

**Scope**
- Add soft deletes to core models.
- Ensure deactivated records are hidden from active lists but remain in historical reports.

**Acceptance Criteria**
- Deleted records are not shown in active CRUD lists.
- Historical reports still include past data tied to deleted records.
- Restore functionality exists for admins.

