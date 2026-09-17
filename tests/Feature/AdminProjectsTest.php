<?php

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
        ->and($project->sectors->pluck('id')->all())->toBe([$sector->id])
        ->and($project->hasMedia('cover'))->toBeTrue()
        ->and($project->getMedia('gallery'))->toHaveCount(2);
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
