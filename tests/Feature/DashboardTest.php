<?php

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
