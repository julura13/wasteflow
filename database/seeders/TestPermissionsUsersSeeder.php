<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

/**
 * Creates one test user per permission role for testing the permission system.
 * Not included in DatabaseSeeder – run manually when needed:
 *
 *   php artisan db:seed --class=TestPermissionsUsersSeeder
 *
 * All test users use password: password
 * Emails: test-admin@test.local, test-manager@test.local, etc.
 */
class TestPermissionsUsersSeeder extends Seeder
{
    private const PASSWORD = 'password';

    private const USERS = [
        ['email' => 'test-admin@test.local', 'name' => 'Test Admin', 'role' => 'admin'],
        ['email' => 'test-client@test.local', 'name' => 'Test Client', 'role' => 'client'],
        ['email' => 'test-document-capture@test.local', 'name' => 'Test Document Capture', 'role' => 'document_capture'],
        ['email' => 'test-manager@test.local', 'name' => 'Test Manager', 'role' => 'manager'],
        ['email' => 'test-operator@test.local', 'name' => 'Test Operator', 'role' => 'operator'],
        ['email' => 'test-order-creator@test.local', 'name' => 'Test Order Creator', 'role' => 'order_creator'],
        ['email' => 'test-order-finalizer@test.local', 'name' => 'Test Order Finalizer', 'role' => 'order_finalizer'],
        ['email' => 'test-weights-capture@test.local', 'name' => 'Test Weights Capture', 'role' => 'weights_capture'],
    ];

    public function run(): void
    {
        // Ensure roles exist (e.g. if running this seeder alone)
        $this->call(RolePermissionSeeder::class);

        foreach (self::USERS as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => bcrypt(self::PASSWORD),
                    'is_active' => true,
                ]
            );

            $role = Role::where('name', $data['role'])->first();
            if ($role && ! $user->hasRole($data['role'])) {
                $user->assignRole($role);
            }

            $this->command->info("Test user: {$data['email']} ({$data['role']})");
        }

        $this->command->info('All test permission users created/updated. Password for all: ' . self::PASSWORD);
    }
}
