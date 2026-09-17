<?php

use App\Models\Statistic;

test('toggles statistic visibility from off to on', function () {
    $statistic = Statistic::factory()->create(['key' => 'projets', 'is_active' => false]);

    $this->artisan('statistics:toggle', ['key' => 'projets'])
        ->assertSuccessful();

    expect($statistic->fresh()->is_active)->toBeTrue();
});

test('toggles statistic visibility from on to off', function () {
    Statistic::factory()->create(['key' => 'projets', 'is_active' => true]);

    $this->artisan('statistics:toggle', ['key' => 'projets'])
        ->assertSuccessful();

    expect(Statistic::where('key', 'projets')->first()->is_active)->toBeFalse();
});

test('fails for unknown statistic key', function () {
    $this->artisan('statistics:toggle', ['key' => 'inconnu'])
        ->assertFailed();
});

test('homepage shows only active statistics', function () {
    Statistic::factory()->create(['value' => '250', 'label' => 'Projets livrés', 'is_active' => true]);
    Statistic::factory()->create(['value' => '999', 'label' => 'Brouillon caché', 'is_active' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('250')
        ->assertSee('Projets livrés')
        ->assertDontSee('Brouillon caché');
});

test('homepage hides statistics band without active content', function () {
    Statistic::factory()->create(['is_active' => false]);

    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('Projets livrés');
});
