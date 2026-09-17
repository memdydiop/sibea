<?php

use App\Models\Expertise;
use App\Models\Sector;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['manage_sectors', 'view_sectors'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

test('forbids deleting a locked sector even with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_sectors');

    $sector = Sector::factory()->create(['is_locked' => true]);

    expect($user->can('delete', $sector))->toBeFalse();
});

test('allows deleting an unlocked sector with permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('manage_sectors');

    $sector = Sector::factory()->create(['is_locked' => false]);

    expect($user->can('delete', $sector))->toBeTrue();
});

test('forbids deleting a sector without permission', function () {
    $user = User::factory()->create();

    $sector = Sector::factory()->create(['is_locked' => false]);

    expect($user->can('delete', $sector))->toBeFalse();
});

test('filters active sectors and orders them', function () {
    Sector::factory()->create(['name' => 'Zulu', 'slug' => 'zulu', 'is_active' => false, 'sort_order' => 1]);
    Sector::factory()->create(['name' => 'Alpha', 'slug' => 'alpha', 'is_active' => true, 'sort_order' => 2]);
    Sector::factory()->create(['name' => 'Beta', 'slug' => 'beta', 'is_active' => true, 'sort_order' => 1]);

    $names = Sector::active()->ordered()->pluck('name')->all();

    expect($names)->toBe(['Beta', 'Alpha']);
});

test('links sectors and expertises many to many', function () {
    $sector = Sector::factory()->create();
    $expertise = Expertise::factory()->create();

    $sector->expertises()->attach($expertise->id);

    expect($sector->expertises()->whereKey($expertise->id)->exists())->toBeTrue()
        ->and($expertise->sectors()->whereKey($sector->id)->exists())->toBeTrue();
});
