<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('creates a super administrator', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin SIBEA',
        '--email' => 'admin@sibea.ci',
        '--password' => 'mot-de-passe-solide',
    ])->assertSuccessful();

    $user = User::where('email', 'admin@sibea.ci')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('Super administrateur'))->toBeTrue()
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('mot-de-passe-solide', $user->password))->toBeTrue();
});

test('is idempotent and leaves credentials untouched without force', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin SIBEA',
        '--email' => 'admin@sibea.ci',
        '--password' => 'premier-mot-de-passe',
    ])->assertSuccessful();

    $this->artisan('app:create-admin', [
        '--name' => 'Autre Nom',
        '--email' => 'admin@sibea.ci',
        '--password' => 'second-mot-de-passe',
    ])->assertSuccessful();

    $user = User::where('email', 'admin@sibea.ci')->sole();

    expect($user->name)->toBe('Admin SIBEA')
        ->and(Hash::check('premier-mot-de-passe', $user->password))->toBeTrue()
        ->and(Hash::check('second-mot-de-passe', $user->password))->toBeFalse();
});

test('resets credentials with force', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin SIBEA',
        '--email' => 'admin@sibea.ci',
        '--password' => 'premier-mot-de-passe',
    ])->assertSuccessful();

    $this->artisan('app:create-admin', [
        '--name' => 'Admin SIBEA 2',
        '--email' => 'admin@sibea.ci',
        '--password' => 'second-mot-de-passe',
        '--force' => true,
    ])->assertSuccessful();

    $user = User::where('email', 'admin@sibea.ci')->sole();

    expect($user->name)->toBe('Admin SIBEA 2')
        ->and(Hash::check('second-mot-de-passe', $user->password))->toBeTrue();
});

test('generates a password when requested', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin SIBEA',
        '--email' => 'admin@sibea.ci',
        '--generate-password' => true,
    ])->expectsOutputToContain('Mot de passe généré')
        ->assertSuccessful();

    expect(User::where('email', 'admin@sibea.ci')->exists())->toBeTrue();
});

test('rejects an invalid payload', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'A',
        '--email' => 'pas-un-email',
        '--password' => 'court',
    ])->assertFailed();

    expect(User::where('email', 'pas-un-email')->exists())->toBeFalse();
});
