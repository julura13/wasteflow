<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Branch;
use App\Models\Site;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\Material;
use App\Models\WasteStream;
use App\Models\Grade;
use App\Models\ContainerOption;
use App\Models\Classification;
use App\Models\Facility;
use App\Models\ServiceProvider;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MonthlyReportDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('name', 'ABC Company')->first();
        
        if (!$company) {
            $this->command->error('ABC Company not found. Please run CompanySeeder first.');
            return;
        }

        $branch = $company->branches()->first();
        if (!$branch) {
            $this->command->error('ABC Company branch not found.');
            return;
        }

        $site = $branch->sites()->first();
        if (!$site) {
            $this->command->error('ABC Company site not found.');
            return;
        }

        $serviceProvider = ServiceProvider::first();
        if (!$serviceProvider) {
            $this->command->error('No service provider found. Please run ServiceProviderSeeder first.');
            return;
        }

        $adminUser = User::where('email', 'admin@wasteflow.example.com')->first();
        if (!$adminUser) {
            $adminUser = User::first();
        }

        $wasteStreams = [
            'General Waste' => WasteStream::firstOrCreate(['name' => 'Waste'], ['is_active' => true]),
            'Recycling' => WasteStream::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]),
            'Organics Recovered' => WasteStream::firstOrCreate(['name' => 'Organic Waste'], ['is_active' => true]),
        ];

        $containerOption = ContainerOption::firstOrCreate(
            ['name' => 'Loose'],
            ['description' => 'Loose material', 'is_active' => true]
        );

        $classification = Classification::firstOrCreate(
            ['name' => 'Recycling'],
            ['description' => 'Recyclable material', 'is_active' => true]
        );

        $facility = Facility::firstOrCreate(
            ['name' => 'Recycling Facility'],
            [
                'description' => 'General recycling facility',
                'facility_type' => 'recycling',
                'requires_weight' => true,
                'is_active' => true,
            ]
        );

        $grades = [
            'General Waste' => Grade::firstOrCreate(['name' => 'General Waste'], ['is_active' => true]),
            'CMW' => Grade::firstOrCreate(['name' => 'CMW'], ['is_active' => true]),
            'HL 1' => Grade::firstOrCreate(['name' => 'HL 1'], ['is_active' => true]),
            'HD' => Grade::firstOrCreate(['name' => 'HD'], ['is_active' => true]),
            'LD Mix' => Grade::firstOrCreate(['name' => 'LD Mix'], ['is_active' => true]),
            'PET Mix' => Grade::firstOrCreate(['name' => 'PET Mix'], ['is_active' => true]),
            'Alu Cans' => Grade::firstOrCreate(['name' => 'Alu Cans'], ['is_active' => true]),
            'Light Steel' => Grade::firstOrCreate(['name' => 'Light Steel'], ['is_active' => true]),
            'Glass' => Grade::firstOrCreate(['name' => 'Glass'], ['is_active' => true]),
            'Tetrapak' => Grade::firstOrCreate(['name' => 'Tetrapak'], ['is_active' => true]),
            'Tissue Paper' => Grade::firstOrCreate(['name' => 'Tissue Paper'], ['is_active' => true]),
            'K4' => Grade::firstOrCreate(['name' => 'K4'], ['is_active' => true]),
            'EPS' => Grade::firstOrCreate(['name' => 'EPS'], ['is_active' => true]),
            'Organics Recovered' => Grade::firstOrCreate(['name' => 'Organics Recovered'], ['is_active' => true]),
        ];

        $materials = [];
        foreach ($grades as $gradeName => $grade) {
            $wasteStreamName = 'Recycling';
            if ($gradeName === 'General Waste') {
                $wasteStreamName = 'General Waste';
            } elseif ($gradeName === 'Organics Recovered') {
                $wasteStreamName = 'Organics Recovered';
            }

            $wasteStream = $wasteStreams[$wasteStreamName] ?? $wasteStreams['Recycling'];
            
            $classificationName = 'Disposed';
            if ($wasteStreamName === 'Recycling') {
                $classificationName = 'Recycling';
            } elseif ($wasteStreamName === 'Organics Recovered') {
                $classificationName = 'Recovered';
            }

            $materialClassification = Classification::firstOrCreate(
                ['name' => $classificationName],
                ['description' => null, 'is_active' => true]
            );

            $materials[$gradeName] = Material::firstOrCreate(
                [
                    'waste_stream_id' => $wasteStream->id,
                    'grade_id' => $grade->id,
                    'container_option_id' => $containerOption->id,
                ],
                [
                    'classification_id' => $materialClassification->id,
                    'facility_id' => $facility->id,
                    'rebate_offered' => $wasteStreamName === 'Recycling',
                    'rebate_rate' => $wasteStreamName === 'Recycling' ? rand(1, 20) / 10 : null,
                    'client_rebate_share' => 100,
                    'is_active' => true,
                ]
            );
        }

        $month = Carbon::parse('2025-12-01');
        $startDate = $month->copy()->startOfMonth();
        $endDate = $month->copy()->endOfMonth();

        $orders = [
            [
                'order_type' => 'waste',
                'status' => 'finalized',
                'requested_collection_date' => $startDate->copy()->addDays(2),
                'actual_collection_date' => $startDate->copy()->addDays(2),
                'waste_streams' => [
                    ['grade' => 'General Waste', 'weight' => 20.00],
                ],
            ],
            [
                'order_type' => 'recycling',
                'status' => 'finalized',
                'requested_collection_date' => $startDate->copy()->addDays(5),
                'actual_collection_date' => $startDate->copy()->addDays(5),
                'waste_streams' => [
                    ['grade' => 'CMW', 'weight' => 301.00],
                    ['grade' => 'HL 1', 'weight' => 158.00],
                    ['grade' => 'HD', 'weight' => 142.00],
                    ['grade' => 'LD Mix', 'weight' => 190.00],
                    ['grade' => 'PET Mix', 'weight' => 84.00],
                    ['grade' => 'Alu Cans', 'weight' => 36.00],
                    ['grade' => 'Light Steel', 'weight' => 20.00],
                    ['grade' => 'Light Steel Cans', 'weight' => 28.00],
                    ['grade' => 'Glass', 'weight' => 300.00],
                    ['grade' => 'Tetrapak', 'weight' => 74.00],
                    ['grade' => 'Tissue Paper', 'weight' => 215.00],
                    ['grade' => 'K4', 'weight' => 303.00],
                    ['grade' => 'EPS', 'weight' => 16.00],
                ],
            ],
            [
                'order_type' => 'waste',
                'status' => 'finalized',
                'requested_collection_date' => $startDate->copy()->addDays(10),
                'actual_collection_date' => $startDate->copy()->addDays(10),
                'waste_streams' => [
                    ['grade' => 'Organics Recovered', 'weight' => 10.00],
                ],
            ],
        ];

        foreach ($orders as $orderData) {
            $wasteStreamsData = $orderData['waste_streams'];
            unset($orderData['waste_streams']);

            $order = Order::create([
                'site_id' => $site->id,
                'service_provider_id' => $serviceProvider->id,
                'created_by' => $adminUser->id,
                'order_type' => $orderData['order_type'],
                'status' => $orderData['status'],
                'requested_collection_date' => $orderData['requested_collection_date'],
                'actual_collection_date' => $orderData['actual_collection_date'],
                'quantity_lines' => [
                    ['quantity_type' => 'wheelie_bins', 'quantity' => 1, 'description' => ''],
                ],
            ]);

            foreach ($wasteStreamsData as $streamData) {
                $gradeName = $streamData['grade'];
                $material = $materials[$gradeName] ?? null;
                
                if ($material) {
                    OrderWasteStream::create([
                        'order_id' => $order->id,
                        'material_id' => $material->id,
                        'gross_weight' => $streamData['weight'],
                        'tare_weight' => 0,
                        'nett_weight' => $streamData['weight'],
                    ]);
                }
            }
        }

        if ($this->command) {
            $this->command->info('Sample monthly report data created for ABC Company for ' . $month->format('M-Y'));
            $this->command->info('Created ' . count($orders) . ' finalized orders with waste streams.');
        }
    }
}

