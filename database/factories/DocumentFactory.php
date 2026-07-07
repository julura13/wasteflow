<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $originalName = fake()->slug().'.pdf';

        return [
            'title' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'original_name' => $originalName,
            'file_name' => fake()->uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'disk' => 'local',
            'path' => 'documents/'.fake()->uuid().'.pdf',
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'uploaded_by' => User::factory(),
        ];
    }
}
