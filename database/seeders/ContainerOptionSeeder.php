<?php

namespace Database\Seeders;

use App\Models\ContainerOption;
use Illuminate\Database\Seeder;

class ContainerOptionSeeder extends Seeder
{
    /**
     * Seed the container options used when creating waste orders.
     */
    public function run(): void
    {
        $options = [
            '140l Wheelie Bin - Organic Waste',
            '240l Wheelie Bin - General Waste',
            '240l Wheelie Bin - Organic Waste',
            '6m3 REL Skip - General Waste',
            '6m3 Skip - Builders Rubble',
            '6m3 Skip - General Waste',
            '9m3 Rel Skip - General Waste',
            'Bakkie Load - Non Compactable Waste',
            'Truck Load - Non Compactable Waste',
        ];

        foreach ($options as $name) {
            ContainerOption::firstOrCreate(
                ['name' => $name],
                ['is_active' => true]
            );
        }
    }
}
