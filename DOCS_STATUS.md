# Documentation Status (Code vs SRS)

This file summarizes what the codebase currently implements versus what the SRS expects, so docs can stay aligned with reality.

## Implemented (Confirmed in Code)

1. Company, branch, and site management (CRUD).
2. Service provider management (CRUD).
3. Materials, waste streams, grades, classifications, facilities (CRUD).
4. Order creation with tracking numbers.
5. Order workflow enforcement and status history.
6. Weight capture and waste stream recording for orders.
7. Supporting document upload for orders.
8. Order PDF export and consolidated order PDF export.
9. Rebate tracker report and PDF export.
10. Waste management report and PDF export.
11. Dashboard data aggregation and environmental impact calculations.
12. Roles and permissions management UI.
13. User management UI for staff.

References:
- `routes/web.php`
- `app/Http/Controllers/*`
- `resources/js/Pages/*`

## Implemented but Not Aligned with SRS

1. Order status flow uses `weight_required` and `documents_required` instead of `collected` and `sorted`.
2. Client access is scoped only by `company_id`, not branch/site roles.
3. Clients do not have a separate portal experience.

References:
- `app/Models/Order.php`
- `app/Traits/ScopeByClientTrait.php`

## Missing vs SRS

1. Client portal UX and client site manager scoping.
2. Client-specific configuration (waste types, commodity values, report prefs).
3. CSV/Excel export and bulk data export.
4. Email notifications (order creation, confirmations, status changes) and templates.
5. User activity and login tracking.
6. Full audit trail beyond order status changes.
7. Root order number and order grouping system.
8. Advanced order filtering (date ranges, provider, company/branch/site).
9. Soft deletes and historical data preservation rules.
10. Hosting and HA configuration.

References:
- `MISSING_FEATURES.md`

