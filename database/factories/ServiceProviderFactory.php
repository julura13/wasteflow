<?php

namespace Database\Factories;

use App\Models\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceProvider>
 */
class ServiceProviderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'types' => fake()->randomElements(['waste_collection', 'recycling', 'hazardous', 'general'], 2),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'registration_number' => fake()->unique()->numerify('REG-########'),
            'notes' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }
}
