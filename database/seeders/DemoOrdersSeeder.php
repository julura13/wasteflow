<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\Classification;
use App\Models\Company;
use App\Models\ContainerOption;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\Order;
use App\Models\OrderWasteStream;
use App\Models\ServiceProvider;
use App\Models\User;
use App\Models\WasteStream;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DemoOrdersSeeder extends Seeder
{
    private const ORDERS_PER_COMPANY = 30;

    private const STATUS_WEIGHTS = [
        'finalized' => 55,
        'weight_required' => 15,
        'documents_required' => 10,
        'scheduled' => 12,
        'pending' => 8,
    ];

    public function run(): void
    {
        $companies = Company::with('branches.sites')->get();
        if ($companies->isEmpty()) {
            $this->command?->error('No companies found. Please run CompanySeeder first.');

            return;
        }

        $serviceProviders = ServiceProvider::all();
        if ($serviceProviders->isEmpty()) {
            $this->command?->error('No service providers found. Please run ServiceProviderSeeder first.');

            return;
        }

        $creator = User::first();
        if (! $creator) {
            $this->command?->error('No users found. Please run UserSeeder first.');

            return;
        }

        $materials = $this->materialsPool();
        $wasteContainer = ContainerOption::query()->where('order_type', 'waste')->where('is_active', true)->first();
        $recyclingContainer = ContainerOption::query()->where('order_type', 'recycling')->where('is_active', true)->first();

        $totalCreated = 0;

        foreach ($companies as $company) {
            $site = $company->branches->flatMap(fn ($branch) => $branch->sites)->first();
            $branch = $site?->branch ?? $company->branches->first();

            for ($i = 0; $i < self::ORDERS_PER_COMPANY; $i++) {
                $orderType = fake()->randomElement(['waste', 'recycling']);
                $status = $this->weightedRandomStatus();
                $requestedDate = Carbon::now()->subDays($this->weightedRandomDaysAgo());
                $isCollected = in_array($status, ['weight_required', 'documents_required', 'finalized'], true);
                $actualDate = $isCollected ? $requestedDate->copy()->addDays(rand(0, 2)) : null;

                $container = $orderType === 'recycling' ? $recyclingContainer : $wasteContainer;
                $quantity = rand(1, 4);
                $quantityLines = $container ? [[
                    'container_option_id' => $container->id,
                    'container_option_name' => $container->name,
                    'quantity' => $quantity,
                ]] : [];

                $order = Order::create([
                    'company_id' => $company->id,
                    'branch_id' => $branch?->id,
                    'site_id' => $site?->id,
                    'service_provider_id' => $serviceProviders->random()->id,
                    'created_by' => $creator->id,
                    'order_type' => $orderType,
                    'status' => $status,
                    'requested_collection_date' => $requestedDate,
                    'actual_collection_date' => $actualDate,
                    'quantity_lines' => $quantityLines,
                    'estimated_quantity' => $quantity,
                    'actual_quantity' => $isCollected ? $quantity : null,
                ]);

                if (in_array($status, ['weight_required', 'finalized'], true)) {
                    $pool = $orderType === 'recycling' ? $materials['recycling'] : $materials['waste'];
                    $lines = fake()->randomElements($pool, rand(1, min(4, count($pool))));

                    foreach ($lines as $material) {
                        $weight = fake()->randomFloat(2, 5, 350);
                        OrderWasteStream::create([
                            'order_id' => $order->id,
                            'material_id' => $material->id,
                            'gross_weight' => $weight,
                            'tare_weight' => 0,
                            'nett_weight' => $weight,
                        ]);
                    }
                }

                $this->logActivity($order, $creator, $status);

                $totalCreated++;
            }
        }

        $this->command?->info("Created {$totalCreated} demo orders across {$companies->count()} companies (".self::ORDERS_PER_COMPANY.' each).');
    }

    private function logActivity(Order $order, User $creator, string $status): void
    {
        ActivityLog::create([
            'log_name' => 'order_created',
            'description' => "Order {$order->tracking_number} created",
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'causer_id' => $creator->id,
            'properties' => ['tracking_number' => $order->tracking_number, 'order_type' => $order->order_type],
        ]);

        if ($status === 'pending') {
            return;
        }

        ActivityLog::create([
            'log_name' => 'order_status_changed',
            'description' => "Order {$order->tracking_number} status changed from pending to {$status}",
            'subject_type' => Order::class,
            'subject_id' => $order->id,
            'causer_id' => $creator->id,
            'properties' => ['tracking_number' => $order->tracking_number, 'old_status' => 'pending', 'new_status' => $status],
        ]);
    }

    /**
     * @return array{waste: array<Material>, recycling: array<Material>}
     */
    private function materialsPool(): array
    {
        $wasteStream = WasteStream::firstOrCreate(['name' => 'Waste'], ['is_active' => true]);
        $recyclingStream = WasteStream::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);

        $disposed = Classification::firstOrCreate(['name' => 'Disposed'], ['is_active' => true]);
        $recycling = Classification::firstOrCreate(['name' => 'Recycling'], ['is_active' => true]);

        $facility = Facility::firstOrCreate(
            ['name' => 'Recycling Facility'],
            [
                'description' => 'General recycling facility',
                'facility_type' => 'recycling',
                'requires_weight' => true,
                'is_active' => true,
            ]
        );

        $wasteGrades = ['General Waste'];
        $recyclingGrades = ['CMW', 'HL 1', 'HD', 'LD Mix', 'PET Mix', 'Alu Cans', 'Light Steel', 'Glass', 'Tetrapak', 'K4'];

        $wasteMaterials = collect($wasteGrades)->map(function ($name) use ($wasteStream, $disposed, $facility) {
            $grade = Grade::firstOrCreate(['name' => $name], ['is_active' => true]);

            return Material::firstOrCreate(
                [
                    'waste_stream_id' => $wasteStream->id,
                    'grade_id' => $grade->id,
                    'classification_id' => $disposed->id,
                    'facility_id' => $facility->id,
                    'service_provider_id' => null,
                ],
                ['rebate_offered' => false, 'client_rebate_share' => 100, 'is_active' => true]
            );
        });

        $recyclingMaterials = collect($recyclingGrades)->map(function ($name) use ($recyclingStream, $recycling, $facility) {
            $grade = Grade::firstOrCreate(['name' => $name], ['is_active' => true]);

            return Material::firstOrCreate(
                [
                    'waste_stream_id' => $recyclingStream->id,
                    'grade_id' => $grade->id,
                    'classification_id' => $recycling->id,
                    'facility_id' => $facility->id,
                    'service_provider_id' => null,
                ],
                ['rebate_offered' => true, 'rebate_rate' => rand(1, 20) / 10, 'client_rebate_share' => 100, 'is_active' => true]
            );
        });

        return ['waste' => $wasteMaterials->all(), 'recycling' => $recyclingMaterials->all()];
    }

    /**
     * Skew heavily toward recent dates so the dashboard's default "this month" view
     * and its "last 7 days" widget both have plenty of data, while still spreading
     * some orders further back for the year-over-year trend charts.
     */
    private function weightedRandomDaysAgo(): int
    {
        $roll = rand(1, 100);

        return match (true) {
            $roll <= 20 => rand(0, 7),
            $roll <= 45 => rand(8, 30),
            $roll <= 75 => rand(31, 90),
            default => rand(91, 365),
        };
    }

    private function weightedRandomStatus(): string
    {
        $total = array_sum(self::STATUS_WEIGHTS);
        $roll = rand(1, $total);

        foreach (self::STATUS_WEIGHTS as $status => $weight) {
            $roll -= $weight;
            if ($roll <= 0) {
                return $status;
            }
        }

        return array_key_first(self::STATUS_WEIGHTS);
    }
}
