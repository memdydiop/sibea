<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'author_name' => fake()->name(),
            'position' => fake()->jobTitle(),
            'organization' => fake()->company(),
            'content' => fake()->paragraph(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
