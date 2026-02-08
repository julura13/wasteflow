<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'view-dashboard',
            'view-orders',
            'create-orders',
            'view-reports',
            'view-reports-all', // see reports for any company (staff); without it, reports scoped to user's company
            'manage-waste-collections',
            'manage-clients',
            'manage-services',
            'manage-settings',
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
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $operatorRole = Role::firstOrCreate(['name' => 'operator']);
        $clientRole = Role::firstOrCreate(['name' => 'client']);
        // Data capture roles (WasteFlow staff - different order stages)
        $orderCreatorRole = Role::firstOrCreate(['name' => 'order_creator']);
        $documentCaptureRole = Role::firstOrCreate(['name' => 'document_capture']);
        $weightsCaptureRole = Role::firstOrCreate(['name' => 'weights_capture']);
        $orderFinalizerRole = Role::firstOrCreate(['name' => 'order_finalizer']);

        // Assign permissions to roles
        $adminRole->syncPermissions(Permission::all());

        $managerRole->syncPermissions([
            'view-dashboard',
            'view-orders',
            'create-orders',
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
    }
}