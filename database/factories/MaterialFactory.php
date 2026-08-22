<?php

namespace Database\Factories;

use App\Models\Classification;
use App\Models\Facility;
use App\Models\Grade;
use App\Models\Material;
use App\Models\WasteStream;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Material>
 */
class MaterialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'waste_stream_id' => WasteStream::factory(),
            'grade_id' => Grade::factory(),
            'classification_id' => Classification::factory(),
            'facility_id' => Facility::factory(),
            'service_provider_id' => null,
            'weight_required' => 'Yes',
            'rebate_offered' => false,
            'rebate_rate' => null,
            'client_rebate_share' => null,
            'backing_document' => false,
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
