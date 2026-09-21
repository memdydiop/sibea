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

test('assigns direct permissions to a user outside roles', function () {
    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('name', 'Awa Koné')
        ->set('email', 'awa-direct@example.com')
        ->set('role_names', [])
        ->set('permission_names', ['manage_leads'])
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'awa-direct@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasDirectPermission('manage_leads'))->toBeTrue()
        ->and($user->roles)->toBeEmpty();
});

test('direct permissions covered by roles are hidden from the assignable list', function () {
    Role::create(['name' => 'Commercial', 'guard_name' => 'web'])->givePermissionTo('manage_leads');

    $assignable = Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('role_names', ['Commercial'])
        ->get('assignablePermissions')->pluck('name')->all();

    expect($assignable)->not->toContain('manage_leads')
        ->and($assignable)->toContain('manage_users');
});

test('hidden direct permissions are preserved on save', function () {
    Role::create(['name' => 'Commercial', 'guard_name' => 'web'])->givePermissionTo('manage_leads');
    Permission::create(['name' => 'manage_pages', 'guard_name' => 'web']);
    $user = User::factory()->create(['password_changed_at' => null]);
    $user->assignRole('Commercial');
    $user->givePermissionTo(['manage_leads', 'manage_pages']);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->call('edit', $user->id)
        ->set('permission_names', [])
        ->call('save')
        ->assertHasNoErrors();

    // manage_leads est masquée (couverte par le rôle) donc conservée ;
    // manage_pages était visible et décochée donc retirée.
    expect($user->fresh()->hasDirectPermission('manage_leads'))->toBeTrue()
        ->and($user->fresh()->hasDirectPermission('manage_pages'))->toBeFalse();
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
        ->test('pages::admin.users.index')
        ->set('role_name', 'Rédacteur')
        ->set('role_description', 'Gère les contenus du site.')
        ->set('role_permission_names', ['manage_leads'])
        ->call('saveRole')
        ->assertHasNoErrors();

    $role = Role::where('name', 'Rédacteur')->first();

    expect($role)->not->toBeNull()
        ->and($role->description)->toBe('Gère les contenus du site.')
        ->and($role->hasPermissionTo('manage_leads'))->toBeTrue();
});

test('assigns users to a role from the role form', function () {
    $member = User::factory()->create(['name' => 'Membre Role Test']);
    $outsider = User::factory()->create(['name' => 'Externe Role Test']);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('role_name', 'Rédacteur')
        ->set('role_permission_names', ['manage_leads'])
        ->set('role_user_ids', [$member->id])
        ->call('saveRole')
        ->assertHasNoErrors();

    expect($member->fresh()->hasRole('Rédacteur'))->toBeTrue()
        ->and($outsider->fresh()->hasRole('Rédacteur'))->toBeFalse();
});

test('role details page lists permissions with descriptions', function () {
    $role = Role::create(['name' => 'Support', 'guard_name' => 'web', 'description' => 'Assistance de premier niveau.']);
    Permission::where('name', 'manage_leads')->update(['description' => 'Consulter, assigner et traiter les prospects reçus via le site.']);
    $role->givePermissionTo('manage_leads');

    $this->actingAs(adminManager())
        ->get(route('admin.roles.show', $role))
        ->assertOk()
        ->assertSee('Assistance de premier niveau.')
        ->assertSee('manage_leads')
        ->assertSee('Consulter, assigner et traiter les prospects reçus via le site.');
});

test('role details page edits the role inline', function () {
    $role = Role::create(['name' => 'Support', 'guard_name' => 'web']);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.roles.show', ['role' => $role])
        ->call('edit')
        ->set('name', 'Support N1')
        ->set('description', 'Assistance de premier niveau.')
        ->set('permission_names', ['manage_leads'])
        ->call('save')
        ->assertHasNoErrors();

    expect($role->fresh()->name)->toBe('Support N1')
        ->and($role->fresh()->description)->toBe('Assistance de premier niveau.');
});

test('role details page deletes the role and goes back to users', function () {
    $role = Role::create(['name' => 'Temporaire', 'guard_name' => 'web']);

    Livewire::actingAs(adminManager())
        ->test('pages::admin.roles.show', ['role' => $role])
        ->call('delete')
        ->assertRedirect(route('admin.users'));

    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});

test('seeded roles and permissions carry french descriptions', function () {
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesPermissionsSeeder', '--force' => true]);

    expect(Role::where('name', 'Commercial')->first()->description)->toBe('Traite les demandes reçues via le site : suivi des prospects, assignation et notes. Consulte les secteurs et les réalisations.')
        ->and(Permission::where('name', 'manage_leads')->first()->description)->toBe('Consulter, assigner et traiter les prospects reçus via le site.');
});

test('role details page is forbidden without permission', function () {
    $role = Role::create(['name' => 'Support', 'guard_name' => 'web']);

    $this->actingAs(User::factory()->create())
        ->get(route('admin.roles.show', $role))
        ->assertForbidden();
});

test('forbids users and roles admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.users'))->assertForbidden();
    $this->actingAs($user)->get('/admin/roles')->assertRedirect(route('admin.users'));
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

    $manager = adminManager();

    $suspended = Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->set('statusFilter', 'suspended')
        ->get('users')->pluck('name')->all();

    expect($suspended)->toContain('Compte Suspendu Test')
        ->and($suspended)->not->toContain('Compte Actif Test');

    $active = Livewire::actingAs($manager)
        ->test('pages::admin.users.index')
        ->set('statusFilter', 'active')
        ->get('users')->pluck('name')->all();

    expect($active)->toContain('Compte Actif Test')
        ->and($active)->not->toContain('Compte Suspendu Test');
});

test('filters users by role', function () {
    $role = Role::create(['name' => 'Commercial', 'guard_name' => 'web']);

    User::factory()->create(['name' => 'Compte Commercial Test'])->assignRole($role);
    User::factory()->create(['name' => 'Compte Sans Role Test']);

    $names = Livewire::actingAs(adminManager())
        ->test('pages::admin.users.index')
        ->set('roleFilter', 'Commercial')
        ->get('users')->pluck('name')->all();

    expect($names)->toContain('Compte Commercial Test')
        ->and($names)->not->toContain('Compte Sans Role Test');
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
