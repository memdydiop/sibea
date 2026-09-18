<?php

namespace Database\Factories;

use App\Models\Sector;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Sector>
 */
class SectorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'hero_title' => fake()->sentence(4),
            'hero_description' => fake()->sentence(10),
            'hero_cta_label' => 'Découvrir',
            'is_active' => true,
            'is_locked' => false,
            'sort_order' => 0,
        ];
    }
}
