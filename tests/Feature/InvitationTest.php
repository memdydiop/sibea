<?php

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_users'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function usersManager(): User
{
    $role = Role::create(['name' => 'Gestionnaire utilisateurs', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_users']);

    return User::factory()->create()->assignRole($role);
}

function invitationUrl(User $user): string
{
    return URL::temporarySignedRoute('invitation.accept', now()->addDays(7), ['user' => $user->id]);
}

test('creating a user sends an invitation', function () {
    Notification::fake();

    Livewire::actingAs(usersManager())
        ->test('pages::admin.users.index')
        ->set('name', 'Invité Exemple')
        ->set('email', 'invite@example.com')
        ->call('save')
        ->assertHasNoErrors();

    $user = User::where('email', 'invite@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->password_changed_at)->toBeNull();

    Notification::assertSentTo($user, UserInvitation::class);
});

test('resending an invitation notifies the user', function () {
    Notification::fake();

    $user = User::factory()->create(['password_changed_at' => null]);

    Livewire::actingAs(usersManager())
        ->test('pages::admin.users.index')
        ->call('resendInvitation', $user->id);

    Notification::assertSentTo($user, UserInvitation::class);
});

test('new user form preselects the Commercial role by default', function () {
    Role::create(['name' => 'Commercial', 'guard_name' => 'web']);

    Livewire::actingAs(usersManager())
        ->test('pages::admin.users.index')
        ->call('create')
        ->assertSet('role_names', ['Commercial']);
});

test('invited user can set a password from the signed link', function () {
    $user = User::factory()->create(['password_changed_at' => null]);

    $this->get(invitationUrl($user))->assertOk()->assertSee('Définir votre mot de passe');

    Livewire::test('pages::invitation', ['user' => $user])
        ->set('password', 'N0uveau-P@ssword!')
        ->set('password_confirmation', 'N0uveau-P@ssword!')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('login'));

    expect($user->fresh()->password_changed_at)->not->toBeNull();
});

test('invitation link with a bad signature is forbidden', function () {
    $user = User::factory()->create();

    $this->get(invitationUrl($user).'tampered')->assertForbidden();
});

test('used invitation shows the login notice instead of the form', function () {
    $user = User::factory()->create(['password_changed_at' => now()]);

    $this->get(invitationUrl($user))
        ->assertOk()
        ->assertSee('déjà été utilisée')
        ->assertDontSee('Confirmation');
});

test('expired invitation link is forbidden', function () {
    $user = User::factory()->create(['password_changed_at' => null]);

    $url = invitationUrl($user);

    $this->travel(8)->days();

    $this->get($url)->assertForbidden();
});

test('suspended user cannot use the invitation link', function () {
    $user = User::factory()->create(['password_changed_at' => null]);

    $component = Livewire::test('pages::invitation', ['user' => $user]);

    $user->update(['suspended_at' => now()]);

    $this->get(invitationUrl($user))->assertForbidden();

    $component
        ->set('password', 'N0uveau-P@ssword!')
        ->set('password_confirmation', 'N0uveau-P@ssword!')
        ->call('save')
        ->assertForbidden();
});
