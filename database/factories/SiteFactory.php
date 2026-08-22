<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->streetName().' Collection Point',
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'is_active' => true,
        ];
    }
}
