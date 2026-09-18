<?php

use App\Models\Expertise;
use Database\Seeders\ExpertiseServiceSeeder;
use Database\Seeders\SectorSeeder;

test('expertise seeder creates rich content linked to sectors and is idempotent', function () {
    $this->seed(SectorSeeder::class);
    $this->seed(ExpertiseServiceSeeder::class);

    $expertise = Expertise::where('slug', 'solutions-energetiques')->first();

    expect($expertise)->not->toBeNull()
        ->and($expertise->short_description)->not->toBeEmpty()
        ->and($expertise->description)->not->toBeEmpty()
        ->and($expertise->benefits)->toHaveCount(4)
        ->and($expertise->process_steps)->toHaveCount(4)
        ->and($expertise->sectors->pluck('slug')->all())->toBe(['energie'])
        ->and($expertise->services)->toHaveCount(1)
        ->and(Expertise::count())->toBe(5);

    $this->seed(ExpertiseServiceSeeder::class);

    expect(Expertise::count())->toBe(5)
        ->and($expertise->fresh()->benefits)->toHaveCount(4);
});
