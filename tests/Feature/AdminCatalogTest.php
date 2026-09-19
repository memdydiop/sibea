<?php

use App\Models\Expertise;
use App\Models\Sector;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_expertises', 'manage_services'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function catalogManager(array $permissions): User
{
    $role = Role::create(['name' => 'Catalogue '.fake()->unique()->word(), 'guard_name' => 'web']);
    $role->givePermissionTo(array_merge(['view_dashboard'], $permissions));

    return User::factory()->create()->assignRole($role);
}

test('creates a service linked to expertises', function () {
    $expertise = Expertise::factory()->create();

    Livewire::actingAs(catalogManager(['manage_services']))
        ->test('pages::admin.services.index')
        ->set('name', 'Étude de sol')
        ->set('slug', 'etude-de-sol')
        ->set('expertise_ids', [$expertise->id])
        ->call('save')
        ->assertHasNoErrors();

    $service = Service::where('slug', 'etude-de-sol')->first();

    expect($service)->not->toBeNull()
        ->and($service->expertises->pluck('id')->all())->toBe([$expertise->id]);
});

test('forbids services admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.services'))->assertForbidden();
});

test('creates an expertise with cover upload and pivots', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create();
    $service = Service::factory()->create();

    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->set('name', 'Gros œuvre')
        ->set('slug', 'gros-oeuvre')
        ->set('benefits_text', "Solidité\nDurabilité")
        ->set('sector_ids', [$sector->id])
        ->set('service_ids', [$service->id])
        ->set('cover', UploadedFile::fake()->image('cover.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $expertise = Expertise::where('slug', 'gros-oeuvre')->first();

    expect($expertise)->not->toBeNull()
        ->and($expertise->benefits)->toBe(['Solidité', 'Durabilité'])
        ->and($expertise->sectors->pluck('id')->all())->toBe([$sector->id])
        ->and($expertise->services->pluck('id')->all())->toBe([$service->id])
        ->and($expertise->hasMedia('cover'))->toBeTrue()
        ->and($expertise->getFirstMedia('cover')->file_name)->toEndWith('.webp');
});

test('removes expertise cover', function () {
    Storage::fake('public');
    $expertise = Expertise::factory()->create();
    $expertise->addMediaFromString(fakeJpeg())->usingFileName('cover.jpg')->toMediaCollection('cover');

    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->call('edit', $expertise->id)
        ->call('removeCover');

    $expertise->refresh();

    expect($expertise->hasMedia('cover'))->toBeFalse();
});

test('truncates long original file names', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create();
    $service = Service::factory()->create();

    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->set('name', 'Nom long')
        ->set('slug', 'nom-long')
        ->set('benefits_text', 'Benefice')
        ->set('sector_ids', [$sector->id])
        ->set('service_ids', [$service->id])
        ->set('cover', UploadedFile::fake()->image(str_repeat('a', 300).'.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $media = Expertise::where('slug', 'nom-long')->first()?->getFirstMedia('cover');

    expect($media)->not->toBeNull()
        ->and(strlen($media->file_name))->toBeLessThanOrEqual(255)
        ->and(strlen($media->name))->toBeLessThanOrEqual(255)
        ->and($media->file_name)->toEndWith('.webp');
});

test('handles file names with invalid utf-8 bytes', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create();
    $service = Service::factory()->create();

    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->set('name', 'Encodage')
        ->set('slug', 'encodage')
        ->set('benefits_text', 'Benefice')
        ->set('sector_ids', [$sector->id])
        ->set('service_ids', [$service->id])
        ->set('cover', UploadedFile::fake()->image("photo-\xff\xfe.jpg"))
        ->call('save')
        ->assertHasNoErrors();

    $media = Expertise::where('slug', 'encodage')->first()?->getFirstMedia('cover');

    expect($media)->not->toBeNull()
        ->and(mb_check_encoding($media->file_name, 'UTF-8'))->toBeTrue()
        ->and($media->file_name)->toEndWith('.webp');
});

test('rejects oversized uploads', function () {
    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->set('name', 'Trop lourd')
        ->set('slug', 'trop-lourd')
        ->set('cover', UploadedFile::fake()->image('cover.jpg')->size(6000))
        ->call('save')
        ->assertHasErrors(['cover']);

    $this->assertDatabaseMissing('expertises', ['slug' => 'trop-lourd']);
});

test('forbids expertises admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.expertises'))->assertForbidden();
});

test('filters expertises by status', function () {
    Expertise::factory()->create(['name' => 'Expertise Active Test', 'slug' => 'expertise-active-test', 'is_active' => true]);
    Expertise::factory()->create(['name' => 'Expertise Inactive Test', 'slug' => 'expertise-inactive-test', 'is_active' => false]);

    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->set('statusFilter', 'active')
        ->assertSee('Expertise Active Test')
        ->assertDontSee('Expertise Inactive Test')
        ->set('statusFilter', 'inactive')
        ->assertSee('Expertise Inactive Test')
        ->assertDontSee('Expertise Active Test');
});

test('paginates expertises with custom per page', function () {
    foreach (range(1, 6) as $i) {
        Expertise::factory()->create(['name' => "Expertise Pagination {$i}", 'slug' => "expertise-pagination-{$i}"]);
    }

    Livewire::actingAs(catalogManager(['manage_expertises']))
        ->test('pages::admin.expertises.index')
        ->set('perPage', 5)
        ->assertSee('Expertise Pagination 1')
        ->assertDontSee('Expertise Pagination 6');
});

test('filters services by status', function () {
    Service::factory()->create(['name' => 'Service Actif Test', 'slug' => 'service-actif-test', 'is_active' => true]);
    Service::factory()->create(['name' => 'Service Inactif Test', 'slug' => 'service-inactif-test', 'is_active' => false]);

    Livewire::actingAs(catalogManager(['manage_services']))
        ->test('pages::admin.services.index')
        ->set('statusFilter', 'active')
        ->assertSee('Service Actif Test')
        ->assertDontSee('Service Inactif Test')
        ->set('statusFilter', 'inactive')
        ->assertSee('Service Inactif Test')
        ->assertDontSee('Service Actif Test');
});
