<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('users who never changed their password are redirected to the security page', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'password_changed_at' => null,
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertRedirect(route('security.edit'));
});

test('users who never changed their password are redirected from the admin', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'password_changed_at' => null,
    ]);

    $this->actingAs($user)
        ->get('/admin')
        ->assertRedirect(route('security.edit'));
});

test('users who changed their password can access protected pages', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'password_changed_at' => now(),
    ]);

    $this->actingAs($user)
        ->get(route('profile.edit'))
        ->assertOk();
});

test('changing the password clears the forced redirection', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
        'password_changed_at' => null,
    ]);

    $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()]);

    Livewire::test('pages::settings.security')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect($user->refresh()->password_changed_at)->not->toBeNull();

    $this->get(route('profile.edit'))->assertOk();
});
