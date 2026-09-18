<?php

use App\Models\Sector;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_sectors'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function sectorPageManager(): User
{
    $role = Role::create(['name' => 'Éditeur pages secteur', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_sectors']);

    return User::factory()->create()->assignRole($role);
}

test('updates sector page content', function () {
    $sector = Sector::factory()->create(['name' => 'BTP', 'slug' => 'btp']);

    Livewire::actingAs(sectorPageManager())
        ->test('pages::admin.sector-pages.index')
        ->call('edit', $sector->id)
        ->set('intro_title', 'Une expertise globale.')
        ->set('intro_text', 'Texte introductif.')
        ->set('cards', [['title' => 'VRD', 'text' => 'Voirie et réseaux.']])
        ->set('figures', [['value' => '120', 'label' => 'km de voies']])
        ->set('cta_title', 'Un projet en vue ?')
        ->set('cta_text', 'Parlons-en.')
        ->set('cta_label', 'Solliciter une étude')
        ->call('save')
        ->assertHasNoErrors();

    $sector->refresh();

    expect($sector->page_intro_title)->toBe('Une expertise globale.')
        ->and($sector->page_intro_text)->toBe('Texte introductif.')
        ->and($sector->page_cards)->toBe([['title' => 'VRD', 'text' => 'Voirie et réseaux.']])
        ->and($sector->page_figures)->toBe([['value' => '120', 'label' => 'km de voies']])
        ->and($sector->page_cta_label)->toBe('Solliciter une étude');
});

test('forbids sector pages admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.sector-pages'))->assertForbidden();
});
