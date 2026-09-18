<?php

use App\Models\User;

test('creates a super administrator', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'Admin SIBEA',
        '--email' => 'admin@sibea.ci',
        '--password' => 'mot-de-passe-solide',
    ])->assertSuccessful();

    $user = User::where('email', 'admin@sibea.ci')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasRole('Super administrateur'))->toBeTrue();
});

test('updates an existing administrator instead of duplicating it', function () {
    $payload = [
        '--name' => 'Admin SIBEA',
        '--email' => 'admin@sibea.ci',
        '--password' => 'mot-de-passe-solide',
    ];

    $this->artisan('app:create-admin', $payload)->assertSuccessful();
    $this->artisan('app:create-admin', $payload)->assertSuccessful();

    expect(User::where('email', 'admin@sibea.ci')->count())->toBe(1);
});

test('rejects an invalid payload', function () {
    $this->artisan('app:create-admin', [
        '--name' => 'A',
        '--email' => 'pas-un-email',
        '--password' => 'court',
    ])->assertFailed();

    expect(User::where('email', 'pas-un-email')->exists())->toBeFalse();
});
