<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state. Defaults to a standalone SHEQ Compliance
     * document (no mediable owner) since that's the only collection with a factory
     * today; override mediable_type/mediable_id/collection for order-attached media.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = fake()->slug().'.pdf';

        return [
            'mediable_type' => null,
            'mediable_id' => null,
            'collection' => 'sheq_compliance',
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'original_name' => $originalName,
            'file_name' => fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'path' => 'sheq-compliance/'.fake()->uuid().'.pdf',
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_by' => User::factory(),
        ];
    }
}
