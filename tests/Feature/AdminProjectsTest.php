<?php

use App\Enums\ProjectStatus;
use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_projects', 'view_projects'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function projectManager(): User
{
    $role = Role::create(['name' => 'Chef de projets', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_projects']);

    return User::factory()->create()->assignRole($role);
}

test('creates a project with gallery and pivots', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create();
    $expertise = Expertise::factory()->create();
    $service = Service::factory()->create();

    Livewire::actingAs(projectManager())
        ->test('pages::admin.projects.index')
        ->set('title', 'Résidence Les Palmiers')
        ->set('slug', 'residence-les-palmiers')
        ->set('is_published', true)
        ->set('status', ProjectStatus::Livre->value)
        ->set('sector_ids', [$sector->id])
        ->set('expertise_ids', [$expertise->id])
        ->set('service_ids', [$service->id])
        ->set('cover', UploadedFile::fake()->image('cover.jpg'))
        ->set('gallery', [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.jpg')])
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::where('slug', 'residence-les-palmiers')->first();

    expect($project)->not->toBeNull()
        ->and($project->is_published)->toBeTrue()
        ->and($project->status)->toBe(ProjectStatus::Livre)
        ->and($project->sectors->pluck('id')->all())->toBe([$sector->id])
        ->and($project->hasMedia('cover'))->toBeTrue()
        ->and($project->getMedia('gallery'))->toHaveCount(2);
});

test('saves editorial content, coordinates and documents', function () {
    Storage::fake('public');

    Livewire::actingAs(projectManager())
        ->test('pages::admin.projects.index')
        ->set('title', 'Rizerie moderne')
        ->set('slug', 'rizerie-moderne')
        ->set('challenge', 'Contexte du projet.')
        ->set('solution', 'Notre réponse.')
        ->set('impact', 'Impact mesuré.')
        ->set('duration', '12 mois')
        ->set('surface', '3 500 m²')
        ->set('budget', '1,1 milliard FCFA')
        ->set('testimonial_author', 'Président de coopérative')
        ->set('testimonial_quote', 'La rizerie tourne en deux équipes.')
        ->set('latitude', '6.8776')
        ->set('longitude', '-6.4502')
        ->set('documents', [UploadedFile::fake()->create('plaquette.pdf', 120, 'application/pdf')])
        ->call('save')
        ->assertHasNoErrors();

    $project = Project::where('slug', 'rizerie-moderne')->first();

    expect($project)->not->toBeNull()
        ->and($project->challenge)->toBe('Contexte du projet.')
        ->and($project->solution)->toBe('Notre réponse.')
        ->and($project->impact)->toBe('Impact mesuré.')
        ->and($project->duration)->toBe('12 mois')
        ->and($project->surface)->toBe('3 500 m²')
        ->and($project->budget)->toBe('1,1 milliard FCFA')
        ->and($project->testimonial_author)->toBe('Président de coopérative')
        ->and($project->latitude)->toBe(6.8776)
        ->and($project->longitude)->toBe(-6.4502)
        ->and($project->hasMedia('documents'))->toBeTrue();
});

test('toggles project publication', function () {
    $project = Project::factory()->create(['is_published' => false]);

    Livewire::actingAs(projectManager())
        ->test('pages::admin.projects.index')
        ->call('togglePublish', $project->id);

    expect($project->fresh()->is_published)->toBeTrue();
});
test('forbids projects admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.projects'))->assertForbidden();
});
