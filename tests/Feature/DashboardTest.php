<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Project;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('admin.dashboard'));
    $response->assertRedirect(route('login'));
});

test('users without permission are forbidden from the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertForbidden();
});

test('authorized users can visit the dashboard', function () {
    Permission::create(['name' => 'view_dashboard', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Lecteur', 'guard_name' => 'web']);
    $role->givePermissionTo('view_dashboard');
    $user = User::factory()->create()->assignRole($role);
    $this->actingAs($user);

    $response = $this->get(route('admin.dashboard'));
    $response->assertOk();
});

test('dashboard lists recent leads and projects for authorized users', function () {
    foreach (['view_dashboard', 'manage_leads', 'view_projects'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
    $role = Role::create(['name' => 'Pilote', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_leads', 'view_projects']);
    $user = User::factory()->create()->assignRole($role);

    Lead::factory()->create(['name' => 'Prospect Récent']);
    Project::factory()->create(['title' => 'Projet Récent', 'is_published' => false]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Derniers prospects')
        ->assertSee('Prospect Récent')
        ->assertSee('Réalisations récentes')
        ->assertSee('Projet Récent')
        ->assertSee('brouillon');
});

test('dashboard hides recent content without the matching permissions', function () {
    Permission::create(['name' => 'view_dashboard', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Lecteur seul', 'guard_name' => 'web']);
    $role->givePermissionTo('view_dashboard');
    $user = User::factory()->create()->assignRole($role);

    Lead::factory()->create(['name' => 'Prospect Caché']);
    Project::factory()->create(['title' => 'Projet Caché']);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('Prospect Caché')
        ->assertDontSee('Projet Caché');
});

test('dashboard shows pending alerts', function () {
    foreach (['view_dashboard', 'manage_leads', 'view_projects'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
    $role = Role::create(['name' => 'Alertes', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_leads', 'view_projects']);
    $user = User::factory()->create()->assignRole($role);

    Lead::factory()->create(['status' => LeadStatus::Nouveau]);
    Project::factory()->create(['is_published' => false]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('À traiter')
        ->assertSee('prospect(s) nouveau(x)')
        ->assertSee('brouillon');
});

test('dashboard hides alerts when nothing is pending', function () {
    foreach (['view_dashboard', 'manage_leads', 'view_projects'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
    $role = Role::create(['name' => 'Calme', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_leads', 'view_projects']);
    $user = User::factory()->create()->assignRole($role);

    Lead::factory()->create(['status' => LeadStatus::Gagne]);
    Project::factory()->create(['is_published' => true]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('À traiter');
});

test('dashboard lists recent activity', function () {
    foreach (['view_dashboard', 'view_projects'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
    $role = Role::create(['name' => 'Activité', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'view_projects']);
    $user = User::factory()->create()->assignRole($role);

    Project::factory()->create(['title' => 'Projet Actif Récent']);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Activité récente')
        ->assertSee('Projet Actif Récent');
});
