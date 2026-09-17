<?php

use App\Models\Sector;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_sectors', 'manage_leads'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function sectorAdmin(): User
{
    $role = Role::create(['name' => 'Gestionnaire secteurs', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_sectors']);

    return User::factory()->create()->assignRole($role);
}

function leadCommercial(): User
{
    $role = Role::create(['name' => 'Commercial', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_leads']);

    return User::factory()->create()->assignRole($role);
}

test('guests are redirected to login', function () {
    $this->get(route('admin.sectors'))->assertRedirect(route('login'));
    $this->get(route('admin.leads'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('forbids sectors admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.sectors'))->assertForbidden();
});

test('forbids sectors admin to commercial profile', function () {
    $commercial = leadCommercial();

    $this->actingAs($commercial)->get(route('admin.sectors'))->assertForbidden();
    $this->actingAs($commercial)->get(route('admin.leads'))->assertOk();
});

test('creates a sector from the modal form', function () {
    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->set('name', 'Voirie')
        ->set('slug', 'voirie')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sectors', ['slug' => 'voirie', 'name' => 'Voirie']);
});

test('validates sector fields', function () {
    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->set('name', '')
        ->set('slug', '')
        ->call('save')
        ->assertHasErrors(['name', 'slug']);

    $this->assertDatabaseCount('sectors', 0);
});

test('refuses to delete a locked sector', function () {
    $sector = Sector::factory()->create(['is_locked' => true]);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('delete', $sector->id)
        ->assertForbidden();

    $this->assertDatabaseHas('sectors', ['id' => $sector->id]);
});

test('deletes an unlocked sector', function () {
    $sector = Sector::factory()->create(['is_locked' => false]);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('delete', $sector->id);

    $this->assertDatabaseMissing('sectors', ['id' => $sector->id]);
});

test('toggles sector visibility', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('toggleActive', $sector->id);

    expect($sector->fresh()->is_active)->toBeFalse();
});

test('searches sectors by name', function () {
    Sector::factory()->create(['name' => 'Voirie', 'slug' => 'voirie']);
    Sector::factory()->create(['name' => 'Assainissement', 'slug' => 'assainissement']);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->set('search', 'voir')
        ->assertSee('Voirie')
        ->assertDontSee('Assainissement');
});

test('invalidates public sectors cache on toggle', function () {
    $sector = Sector::factory()->create(['name' => 'CacheTest', 'slug' => 'cachetest', 'is_active' => true]);

    $this->get(route('home'))->assertSee('CacheTest');

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('toggleActive', $sector->id);

    $this->get(route('home'))->assertDontSee('CacheTest');
});
