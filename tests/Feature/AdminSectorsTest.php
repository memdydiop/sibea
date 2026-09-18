<?php

use App\Models\Sector;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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
        ->assertHasErrors(['name']);

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

test('updates sector hero, page content and image from the merged form', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create(['name' => 'BTP', 'slug' => 'btp', 'hero_title' => 'Ancien titre']);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('edit', $sector->id)
        ->set('hero_title', 'Nouveau titre')
        ->set('hero_description', 'Nouvelle description')
        ->set('hero_cta_label', 'Découvrir')
        ->set('intro_title', 'Une expertise globale.')
        ->set('intro_text', 'Texte introductif.')
        ->set('cards', [['title' => 'VRD', 'text' => 'Voirie et réseaux.']])
        ->set('figures', [['value' => '120', 'label' => 'km de voies']])
        ->set('cta_title', 'Un projet en vue ?')
        ->set('cta_text', 'Parlons-en.')
        ->set('cta_label', 'Solliciter une étude')
        ->set('heroImage', UploadedFile::fake()->image('hero.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $sector->refresh();

    expect($sector->hero_title)->toBe('Nouveau titre')
        ->and($sector->hero_description)->toBe('Nouvelle description')
        ->and($sector->hero_cta_label)->toBe('Découvrir')
        ->and($sector->page_intro_title)->toBe('Une expertise globale.')
        ->and($sector->page_intro_text)->toBe('Texte introductif.')
        ->and($sector->page_cards)->toBe([['title' => 'VRD', 'text' => 'Voirie et réseaux.']])
        ->and($sector->page_figures)->toBe([['value' => '120', 'label' => 'km de voies']])
        ->and($sector->page_cta_title)->toBe('Un projet en vue ?')
        ->and($sector->page_cta_label)->toBe('Solliciter une étude')
        ->and($sector->hasMedia('hero'))->toBeTrue()
        ->and($sector->getFirstMedia('hero')->file_name)->toEndWith('.webp');
});

test('removes the sector hero image', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create();
    $sector->addMediaFromString(fakeJpeg())->usingFileName('hero.jpg')->toMediaCollection('hero');

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('edit', $sector->id)
        ->call('removeHeroImage');

    expect($sector->fresh()->hasMedia('hero'))->toBeFalse();
});

test('redirects the legacy sector pages url', function () {
    $this->actingAs(sectorAdmin())
        ->get('/admin/contenus-secteurs')
        ->assertRedirect('/admin/secteurs');
});

test('generates the slug automatically from the name', function () {
    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->set('name', 'Énergie durable')
        ->set('slug', '')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sectors', ['name' => 'Énergie durable', 'slug' => 'energie-durable']);
});

test('generates a unique slug when it already exists', function () {
    Sector::factory()->create(['name' => 'BTP', 'slug' => 'btp']);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->set('name', 'BTP')
        ->set('slug', '')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sectors', ['name' => 'BTP', 'slug' => 'btp-2']);
});

test('regenerates the slug when renaming a sector with an automatic slug', function () {
    $sector = Sector::factory()->create(['name' => 'BTP', 'slug' => 'btp']);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('edit', $sector->id)
        ->set('name', 'BTP & Génie civil')
        ->call('save')
        ->assertHasNoErrors();

    expect($sector->fresh()->slug)->toBe('btp-genie-civil');
});

test('keeps a custom slug when renaming a sector', function () {
    $sector = Sector::factory()->create(['name' => 'BTP', 'slug' => 'notre-btp']);

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('edit', $sector->id)
        ->set('name', 'BTP & Génie civil')
        ->call('save')
        ->assertHasNoErrors();

    expect($sector->fresh()->slug)->toBe('notre-btp');
});

test('invalidates public sectors cache on toggle', function () {
    $sector = Sector::factory()->create(['name' => 'CacheTest', 'slug' => 'cachetest', 'is_active' => true]);

    $this->get(route('home'))->assertSee('CacheTest');

    Livewire::actingAs(sectorAdmin())
        ->test('pages::admin.sectors.index')
        ->call('toggleActive', $sector->id);

    $this->get(route('home'))->assertDontSee('CacheTest');
});
