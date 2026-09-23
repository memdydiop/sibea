<?php

namespace Database\Factories;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\ProspectType;
use App\Enums\RequestType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'prospect_type' => ProspectType::Particulier,
            'request_type' => RequestType::Information,
            'message' => fake()->paragraph(),
            'status' => LeadStatus::Nouveau,
            'source' => LeadSource::Site,
        ];
    }
}
