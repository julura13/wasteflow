<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClientHubAdvert>
 */
class ClientHubAdvertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'details' => fake()->optional()->paragraph(),
            'contact_email' => 'crm@wasteflow.example.com',
            'file_name' => fake()->uuid().'.png',
            'original_name' => fake()->slug().'.png',
            'mime_type' => 'image/png',
            'disk' => 'local',
            'path' => 'client-hub/'.fake()->uuid().'.png',
            'file_size' => fake()->numberBetween(1024, 5 * 1024 * 1024),
            'is_active' => true,
            'uploaded_by' => User::factory(),
        ];
    }
}
