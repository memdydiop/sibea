<?php

use App\Models\Statistic;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_statistics'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function statisticManager(): User
{
    $role = Role::create(['name' => 'Éditeur statistiques', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_statistics']);

    return User::factory()->create()->assignRole($role);
}

test('creates a statistic with a generated key', function () {
    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->set('label', 'Projets livrés')
        ->set('value', '250')
        ->call('save')
        ->assertHasNoErrors();

    $statistic = Statistic::where('key', 'projets-livres')->first();

    expect($statistic)->not->toBeNull()
        ->and($statistic->value)->toBe('250')
        ->and($statistic->is_active)->toBeFalse();
});

test('validates required statistic fields', function () {
    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->call('save')
        ->assertHasErrors(['label' => 'required', 'value' => 'required']);
});

test('rejects a duplicate statistic key', function () {
    Statistic::factory()->create(['key' => 'projets']);

    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->set('key', 'projets')
        ->set('label', 'Projets')
        ->set('value', '10')
        ->call('save')
        ->assertHasErrors(['key' => 'unique']);
});

test('toggles statistic activation', function () {
    $statistic = Statistic::factory()->create(['is_active' => false]);

    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->call('toggleActive', $statistic->id);

    expect($statistic->fresh()->is_active)->toBeTrue();
});

test('updates a statistic', function () {
    $statistic = Statistic::factory()->create(['value' => '10']);

    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->call('edit', $statistic->id)
        ->set('value', '42')
        ->call('save')
        ->assertHasNoErrors();

    expect($statistic->fresh()->value)->toBe('42');
});

test('deletes a statistic', function () {
    $statistic = Statistic::factory()->create();

    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->call('delete', $statistic->id);

    expect(Statistic::find($statistic->id))->toBeNull();
});

test('forbids statistics admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.statistics'))->assertForbidden();
});

test('searches statistics by label and key', function () {
    Statistic::factory()->create(['label' => 'Projets Livres Recherche', 'key' => 'projets-livres-recherche', 'value' => '250']);
    Statistic::factory()->create(['label' => 'Autre Indicateur', 'key' => 'autre-indicateur', 'value' => '10']);

    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->set('search', 'livres')
        ->assertSee('Projets Livres Recherche')
        ->assertDontSee('Autre Indicateur')
        ->set('search', 'projets-livres-recherche')
        ->assertSee('Projets Livres Recherche')
        ->assertDontSee('Autre Indicateur');
});

test('filters statistics by status', function () {
    Statistic::factory()->create(['label' => 'Statistique Active Test', 'key' => 'statistique-active-test', 'value' => '1', 'is_active' => true]);
    Statistic::factory()->create(['label' => 'Statistique Inactive Test', 'key' => 'statistique-inactive-test', 'value' => '2', 'is_active' => false]);

    Livewire::actingAs(statisticManager())
        ->test('pages::admin.statistics.index')
        ->set('statusFilter', 'active')
        ->assertSee('Statistique Active Test')
        ->assertDontSee('Statistique Inactive Test')
        ->set('statusFilter', 'inactive')
        ->assertSee('Statistique Inactive Test')
        ->assertDontSee('Statistique Active Test');
});
