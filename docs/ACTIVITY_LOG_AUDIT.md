# Activity Log – Audit Trail

This document lists all workflows that write to the `activity_logs` table for audit purposes.

## Log names and where they are written

| Log name | Description | Controller / location |
|----------|-------------|------------------------|
| **Orders** | | |
| `order_created` | New order created | `OrderController::store` |
| `order_updated` | Order details (quantity lines / notes) updated | `OrderController::update` |
| `order_status_changed` | Order status changed (e.g. pending → scheduled) | `OrderController::updateStatus` |
| `order_weights_saved` | Weights captured for order | `OrderController::saveWeights` |
| `order_finalized` | Order finalized with slip number and date | `OrderController::finalize` |
| `order_collection_date_updated` | Actual collection date changed (finalized orders) | `OrderController::updateCollectionDate` |
| `order_consolidated_pdf_scheduled` | Order set to scheduled via consolidated PDF | `OrderController::downloadConsolidatedPDF` |
| `duplicate_slip_number` | Duplicate slip number attempted | `OrderController::checkSlipNumber` |
| `order_deleted` | Order deleted (with reason) | `OrderController::deleteOrder` |
| **Clients / org** | | |
| `company_created` | Company created | `CompanyController::store` |
| `company_updated` | Company updated | `CompanyController::update` |
| `company_deleted` | Company deleted | `CompanyController::destroy` |
| `branch_created` | Branch created | `BranchController::store` |
| `branch_updated` | Branch updated | `BranchController::update` |
| `branch_deleted` | Branch deleted | `BranchController::destroy` |
| `collection_point_created` | Collection point (site) created | `SiteController::store` |
| `collection_point_updated` | Collection point updated | `SiteController::update` |
| `collection_point_deleted` | Collection point deleted | `SiteController::destroy` |
| **Users & roles** | | |
| `user_created` | User created | `UserController::store` |
| `user_updated` | User updated | `UserController::update` |
| `role_created` | Role created | `RoleController::store` |
| `role_updated` | Role updated | `RoleController::update` |
| **Services** | | |
| `material_created` | Material created | `MaterialController::store` |
| `material_updated` | Material updated | `MaterialController::update` |
| `material_deleted` | Material deleted | `MaterialController::destroy` |
| `material_rebate_rate_updated` | Material rebate rate changed | `MaterialController::updateRebateRate` |
| `material_rebate_share_updated` | Material client rebate share changed | `MaterialController::updateRebateShare` |
| `service_provider_created` | Service provider created | `ServiceProviderController::store` |
| `service_provider_updated` | Service provider updated | `ServiceProviderController::update` |
| `service_provider_deleted` | Service provider deleted | `ServiceProviderController::destroy` |
| **Settings** | | |
| `waste_stream_created` | Waste stream created | `Settings\WasteStreamController::store` |
| `waste_stream_updated` | Waste stream updated | `Settings\WasteStreamController::update` |
| `waste_stream_deleted` | Waste stream deleted | `Settings\WasteStreamController::destroy` |
| `grade_created` | Grade created | `Settings\GradeController::store` |
| `grade_updated` | Grade updated | `Settings\GradeController::update` |
| `grade_deleted` | Grade deleted | `Settings\GradeController::destroy` |
| `container_option_created` | Container option created | `Settings\ContainerOptionController::store` |
| `container_option_updated` | Container option updated | `Settings\ContainerOptionController::update` |
| `container_option_deleted` | Container option deleted | `Settings\ContainerOptionController::destroy` |
| `classification_created` | Classification created | `Settings\ClassificationController::store` |
| `classification_updated` | Classification updated | `Settings\ClassificationController::update` |
| `classification_deleted` | Classification deleted | `Settings\ClassificationController::destroy` |
| `facility_created` | Facility created | `Settings\FacilityController::store` |
| `facility_updated` | Facility updated | `Settings\FacilityController::update` |
| `facility_deleted` | Facility deleted | `Settings\FacilityController::destroy` |
| **Media** | | |
| `media_uploaded` | Document uploaded to an order | `MediaController::upload` |
| `media_deleted` | Document deleted | `MediaController::destroy` |
| **Profile** | | |
| `profile_updated` | User profile updated | `ProfileController::update` |
| `profile_avatar_uploaded` | Avatar uploaded | `ProfileController::uploadAvatar` |
| `profile_avatar_deleted` | Avatar deleted | `ProfileController::deleteAvatar` |
| `profile_destroyed` | User account deleted | `ProfileController::destroy` |
| **Seeder** | | |
| `orders_seeded` | Bulk orders created via Order Seeder | `OrderSeederController::store` |

## Value / quantity change tracking

Where we track before-and-after or snapshots in `properties`:

| Log name | What is stored in properties |
|----------|------------------------------|
| `order_created` | `quantity_lines`, `estimated_quantity`, `order_type`, `requested_collection_date` |
| `order_updated` | `old_quantity_lines`, `new_quantity_lines`, `old_estimated_quantity`, `new_estimated_quantity`, `old_notes`, `new_notes` |
| `order_weights_saved` | `weight_lines` (array of `{ material_id, weight }`) |
| `order_finalized` | `slip_number`, `actual_collection_date`, `actual_quantity` |
| `order_status_changed` | `old_status`, `new_status` |
| `order_collection_date_updated` | `old_date`, `new_date` |
| `material_rebate_rate_updated` | `old_rate`, `new_rate` |
| `material_rebate_share_updated` | `old_share`, `new_share` |

Other update events (company, branch, etc.) store the entity name/id but not full old/new field diffs.

## Schema

- `log_name`: short key (e.g. `order_created`).
- `description`: human-readable text.
- `subject_type` / `subject_id`: polymorphic subject (e.g. Order, Company); null for global events like `orders_seeded`.
- `causer_id`: user who performed the action (from `auth()->id()`).
- `properties`: JSON with extra context (ids, names, old/new values where useful).

## Querying for audit

Filter by `log_name` for a given workflow, by `subject_type`/`subject_id` for a specific entity, or by `causer_id` for a user’s actions. Use `created_at` for time range.
