<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@wasteflow.example.com',
            'password' => bcrypt('password'),
        ]);

        // Assign admin role
        $adminRole = Role::where('name', 'admin')->first();
        if ($adminRole) {
            $admin->assignRole($adminRole);
        }

        $user = User::create([
           'name' => 'Site Owner',
            'email' => 'owner@wasteflow.example.com',
            'password' => bcrypt('password'),
        ]);

        $user->assignRole('admin');

        // Create manager user
        $manager = User::create([
            'name' => 'Manager User',
            'email' => 'manager@wasteflow.example.com',
            'password' => bcrypt('password'),
        ]);

        // Assign manager role
        $managerRole = Role::where('name', 'manager')->first();
        if ($managerRole) {
            $manager->assignRole($managerRole);
        }

        // Create operator user
        $operator = User::create([
            'name' => 'Operator User',
            'email' => 'operator@wasteflow.example.com',
            'password' => bcrypt('password'),
        ]);

        // Assign operator role
        $operatorRole = Role::where('name', 'operator')->first();
        if ($operatorRole) {
            $operator->assignRole($operatorRole);
        }

        // WasteFlow Operations users
        $operationsUsers = [
            ['name' => 'Ops Admin 1', 'email' => 'opsadmin1@wasteflow.example.com'],
            ['name' => 'CRM', 'email' => 'crm@wasteflow.example.com'],
            ['name' => 'Sales Exec', 'email' => 'salesexec@wasteflow.example.com'],
            ['name' => 'Info', 'email' => 'info@wasteflow.example.com'],
            ['name' => 'Operations', 'email' => 'operations@wasteflow.example.com'],
        ];

        foreach ($operationsUsers as $opUser) {
            $user = User::create([
                'name' => $opUser['name'],
                'email' => $opUser['email'],
                'password' => bcrypt('password'),
            ]);
            if ($managerRole) {
                $user->assignRole($managerRole);
            }
        }
    }
}
