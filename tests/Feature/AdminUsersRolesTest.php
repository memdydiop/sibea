<?php

use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_users', 'manage_roles', 'manage_leads'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function adminManager(): User
{
    $role = Role::create(['name' => 'Super testeur', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_users', 'manage_roles']);

    return User::factory()->create()->assignRole($role);
}

test('creates a user with roles', function () {
    $role = Role::create(['name' => 'Éditeur', 'guard_name' => 'web']);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('name', 'Awa Koné')
        ->set('email', 'awa@example.com')
        ->set('password', 'S3cur3-P@ssword!')
        ->set('role_names', [$role->name])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'awa@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole($role->name))->toBeTrue();
});

test('refuses weak passwords', function () {
    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('name', 'Faible')
        ->set('email', 'faible@example.com')
        ->set('password', 'court')
        ->call('save')
        ->assertHasErrors(['password']);

    $this->assertDatabaseMissing('users', ['email' => 'faible@example.com']);
});

test('refuses self deletion', function () {
    $manager = adminManager();

    Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->call('delete', $manager->id)
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $manager->id]);
});

test('creates a role with permissions', function () {
    Livewire::actingAs(adminManager())
        ->test('pages::admin.roles.index')
        ->set('name', 'Rédacteur')
        ->set('permission_names', ['manage_leads'])
        ->call('save')
        ->assertHasNoErrors();

    $role = Role::where('name', 'Rédacteur')->first();

    expect($role)->not->toBeNull()
        ->and($role->hasPermissionTo('manage_leads'))->toBeTrue();
});

test('forbids users and roles admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.roles'))->assertForbidden();
});
