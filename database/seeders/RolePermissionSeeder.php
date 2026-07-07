<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Remove dead/duplicate permissions from a previous iteration of the permission set:
        // 'view-orders' and 'create-orders' were superseded by the granular 'orders-view'/'orders-create'
        // below and are no longer checked anywhere; 'manage-permissions' was never checked either
        // ('manage-roles' alone guards the /roles routes). Deleting them also detaches them from any
        // role via Spatie's cascading pivot cleanup.
        Permission::whereIn('name', ['view-orders', 'create-orders', 'manage-permissions'])->get()->each->delete();

        // Create permissions
        $permissions = [
            'view-dashboard',
            'view-reports',
            'view-reports-all', // see reports for any company (staff); without it, reports scoped to user's company
            // Reports calculators (kept behind permissions for now)
            'view-carbon-calculator',
            'view-water-calculator',
            'view-landfill-space-calculator',
            'manage-waste-collections',
            'manage-clients',
            'manage-services',
            'manage-settings',
            'manage-users',
            'manage-roles',
            // Order: view list and detail
            'orders-view',
            // Order workflow (granular)
            'orders-create',
            'orders-schedule',           // change status to scheduled
            'orders-generate-consolidated', // generate consolidated order PDF
            'orders-status-documents-required', // change status to documents_required
            'orders-status-weight-required',    // change status to weight_required
            'orders-capture-documents',   // upload slips/manifests
            'orders-capture-weights',    // capture weights (then status -> documents_required)
            'orders-finalize',
            'view-activity-log',         // view activity log / audit trail (filter by order)
            'manage-recurring-orders',   // create / edit / delete recurring order templates (admin only)
            'manage-documents',          // upload / edit / delete documents (admin only; everyone can view)
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles (updateOrCreate so descriptions stay in sync on reseed)
        $adminRole = Role::updateOrCreate(['name' => 'admin'], [
            'description' => 'Full system access — manages clients, orders, users, roles, settings, and reporting.',
        ]);
        $managerRole = Role::updateOrCreate(['name' => 'manager'], [
            'description' => 'Day-to-day operations — clients, orders, and service data, plus reporting, without user/role administration.',
        ]);
        $operatorRole = Role::updateOrCreate(['name' => 'operator'], [
            'description' => 'Runs the order workflow end-to-end: creates, schedules, and finalizes orders, with reporting access.',
        ]);
        $clientRole = Role::updateOrCreate(['name' => 'client'], [
            'description' => "External customer login — views their own company's dashboard and reports only.",
        ]);
        $companyUserRole = Role::updateOrCreate(['name' => 'company_user'], [
            'description' => 'External user linked to a company — same portal access as client.',
        ]);
        // Data capture roles (WasteFlow staff - different order stages)
        $orderCreatorRole = Role::updateOrCreate(['name' => 'order_creator'], [
            'description' => 'Creates and schedules orders and generates consolidated order documents.',
        ]);
        $documentCaptureRole = Role::updateOrCreate(['name' => 'document_capture'], [
            'description' => 'Uploads collection slips/manifests and marks orders as documents-required.',
        ]);
        $weightsCaptureRole = Role::updateOrCreate(['name' => 'weights_capture'], [
            'description' => 'Captures container/scale weights and marks orders as weight-required.',
        ]);
        $orderFinalizerRole = Role::updateOrCreate(['name' => 'order_finalizer'], [
            'description' => 'Finalizes completed orders.',
        ]);

        // Assign permissions to roles
        // No roles need the calculators yet, but we still register the permissions so they can be granted later.
        $reportCalculatorPermissions = [
            'view-carbon-calculator',
            'view-water-calculator',
            'view-landfill-space-calculator',
        ];
        $adminRole->syncPermissions(Permission::whereNotIn('name', $reportCalculatorPermissions)->get());

        $managerRole->syncPermissions([
            'view-dashboard',
            'view-reports',
            'view-reports-all',
            'manage-waste-collections',
            'manage-clients',
            'manage-services',
            'manage-settings',
            'orders-view',
            'orders-create',
            'orders-schedule',
            'orders-generate-consolidated',
            'orders-status-documents-required',
            'orders-status-weight-required',
            'orders-capture-documents',
            'orders-capture-weights',
            'orders-finalize',
            'view-activity-log',
        ]);

        $operatorRole->syncPermissions([
            'view-dashboard',
            'view-reports',
            'view-reports-all',
            'manage-waste-collections',
            'orders-view',
            'orders-create',
            'orders-schedule',
            'orders-generate-consolidated',
            'orders-status-documents-required',
            'orders-status-weight-required',
            'orders-capture-documents',
            'orders-capture-weights',
            'orders-finalize',
            'view-activity-log',
        ]);

        // Order creator: create, schedule, generate consolidated
        $orderCreatorRole->syncPermissions([
            'view-dashboard',
            'orders-view',
            'orders-create',
            'orders-schedule',
            'orders-generate-consolidated',
        ]);

        // Document capture: upload slips/manifests, set status to documents_required
        $documentCaptureRole->syncPermissions([
            'view-dashboard',
            'orders-view',
            'orders-capture-documents',
            'orders-status-documents-required',
        ]);

        // Weights capture: capture weights, set status to weight_required
        $weightsCaptureRole->syncPermissions([
            'view-dashboard',
            'orders-view',
            'orders-capture-weights',
            'orders-status-weight-required',
        ]);

        // Order finalizer: finalize orders
        $orderFinalizerRole->syncPermissions([
            'view-dashboard',
            'orders-view',
            'orders-finalize',
        ]);

        // Client: dashboard and reports for their company only (no view-reports-all)
        $clientRole->syncPermissions([
            'view-dashboard',
            'view-reports',
        ]);

        // External users linked via company_user pivot; same portal access as client until roles are refined
        $companyUserRole->syncPermissions([
            'view-dashboard',
            'view-reports',
        ]);
    }
}
