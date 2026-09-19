<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        ->set('role_names', [$role->name])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'awa@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole($role->name))->toBeTrue();
});

test('refuses duplicate emails', function () {
    User::factory()->create(['email' => 'doublon@example.com']);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('name', 'Doublon')
        ->set('email', 'doublon@example.com')
        ->call('save')
        ->assertHasErrors(['email']);
});

test('creates users with unique unknown passwords', function () {
    $component = Livewire::actingAs(adminManager())->test('pages::admin.users.index');

    $component->set('name', 'Premier')->set('email', 'premier@example.com')->call('save')->assertHasNoErrors();
    $component->set('name', 'Second')->set('email', 'second@example.com')->call('save')->assertHasNoErrors();

    $first = User::where('email', 'premier@example.com')->first();
    $second = User::where('email', 'second@example.com')->first();

    expect($first)->not->toBeNull()
        ->and($second)->not->toBeNull()
        ->and($first->password)->not->toBeEmpty()
        ->and($first->password)->toStartWith('$2y$')
        ->and($first->password)->not->toBe($second->password)
        ->and($first->password_changed_at)->toBeNull();
});

test('editing a user never changes the password', function () {
    $user = User::factory()->create(['password_changed_at' => null]);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->call('edit', $user->id)
        ->set('name', 'Nom Modifié')
        ->call('save')
        ->assertHasNoErrors();

    expect($user->fresh()->name)->toBe('Nom Modifié')
        ->and(Hash::check('password', $user->fresh()->password))->toBeTrue();
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

test('suspends and reactivates a user with history entries', function () {
    $manager = adminManager();
    $user = User::factory()->create();

    Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->call('suspend', $user->id);

    expect($user->fresh()->suspended_at)->not->toBeNull();

    Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->call('unsuspend', $user->id);

    expect($user->fresh()->suspended_at)->toBeNull();

    $this->assertDatabaseHas('activities', [
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'action' => 'suspended',
    ]);
    $this->assertDatabaseHas('activities', [
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'action' => 'unsuspended',
    ]);
});

test('refuses to suspend yourself', function () {
    $manager = adminManager();

    Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->call('suspend', $manager->id)
        ->assertForbidden();

    expect($manager->fresh()->suspended_at)->toBeNull();
});

test('refuses to delete an active account', function () {
    $user = User::factory()->create();

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->call('delete', $user->id)
        ->assertForbidden();

    $this->assertDatabaseHas('users', ['id' => $user->id]);
});

test('deletes a suspended account with a history entry', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->call('delete', $user->id);

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
    $this->assertDatabaseHas('activities', [
        'subject_type' => User::class,
        'subject_id' => $user->id,
        'action' => 'deleted',
    ]);
});

test('filters users by status', function () {
    User::factory()->create(['name' => 'Compte Actif Test', 'suspended_at' => null]);
    User::factory()->create(['name' => 'Compte Suspendu Test', 'suspended_at' => now()]);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('statusFilter', 'suspended')
        ->assertSee('Compte Suspendu Test')
        ->assertDontSee('Compte Actif Test')
        ->set('statusFilter', 'active')
        ->assertSee('Compte Actif Test')
        ->assertDontSee('Compte Suspendu Test');
});

test('shows the account history', function () {
    $user = User::factory()->create();

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->call('suspend', $user->id)
        ->call('openHistory', $user->id)
        ->assertSee('Historique du compte')
        ->assertSee('Compte suspendu.');
});

test('never stores nor displays a plaintext password', function () {
    $manager = adminManager();

    Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->set('name', 'Secret')
        ->set('email', 'secret@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'secret@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->password)->not->toBeEmpty()
        ->and($user->password)->toStartWith('$2y$');

    $this->actingAs($manager)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertDontSee('type="password"');
});

test('a suspended user cannot log in', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('a suspended session is terminated on admin pages', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
