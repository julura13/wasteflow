<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Client;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'phone' => '+27 11 123 4567',
                'company' => 'Smith Manufacturing',
                'address' => '123 Industrial Road, Johannesburg, 2000',
                'status' => 'active',
                'contract_start_date' => '2024-01-01',
                'contract_end_date' => '2024-12-31',
                'monthly_fee' => 2500.00,
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@retailcorp.co.za',
                'phone' => '+27 21 987 6543',
                'company' => 'Retail Corp',
                'address' => '456 Shopping Mall, Cape Town, 8001',
                'status' => 'active',
                'contract_start_date' => '2024-02-15',
                'contract_end_date' => '2025-02-14',
                'monthly_fee' => 1800.00,
            ],
            [
                'name' => 'Mike Wilson',
                'email' => 'mike.wilson@hospitality.co.za',
                'phone' => '+27 31 555 1234',
                'company' => 'Hospitality Group',
                'address' => '789 Hotel Street, Durban, 4000',
                'status' => 'pending',
                'contract_start_date' => null,
                'contract_end_date' => null,
                'monthly_fee' => 3200.00,
            ],
            [
                'name' => 'Lisa Brown',
                'email' => 'lisa.brown@officecomplex.co.za',
                'phone' => '+27 12 345 6789',
                'company' => 'Office Complex Ltd',
                'address' => '321 Business Park, Pretoria, 0001',
                'status' => 'active',
                'contract_start_date' => '2023-06-01',
                'contract_end_date' => '2024-05-31',
                'monthly_fee' => 4200.00,
            ],
            [
                'name' => 'David Miller',
                'email' => 'david.miller@factory.co.za',
                'phone' => '+27 16 789 0123',
                'company' => 'Miller Factory',
                'address' => '654 Production Avenue, Port Elizabeth, 6000',
                'status' => 'inactive',
                'contract_start_date' => '2023-01-01',
                'contract_end_date' => '2023-12-31',
                'monthly_fee' => 1500.00,
            ],
            [
                'name' => 'Emma Davis',
                'email' => 'emma.davis@mall.co.za',
                'phone' => '+27 11 456 7890',
                'company' => 'City Mall',
                'address' => '987 Commercial Street, Johannesburg, 2000',
                'status' => 'active',
                'contract_start_date' => '2024-03-01',
                'contract_end_date' => '2025-02-28',
                'monthly_fee' => 2800.00,
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}