<?php

use App\Models\Lead;
use App\Models\User;
use App\Notifications\NewLeadNotification;

test('new lead mail contains prospect details and reply-to', function () {
    $lead = Lead::factory()->create([
        'name' => 'Awa Koné',
        'email' => 'awa@example.com',
        'company' => 'SIBEA Test',
        'phone' => '+2250700000000',
        'target_territory' => 'Abidjan, Cocody',
        'budget' => '10M FCFA',
    ]);

    $user = User::factory()->create();
    $mail = (new NewLeadNotification($lead))->toMail($user);
    $rendered = (string) $mail->render();

    expect($mail->replyTo)->toBe([['awa@example.com', 'Awa Koné']]);
    expect($rendered)->toContain('Awa Koné');
    expect($rendered)->toContain('awa@example.com');
    expect($rendered)->toContain('SIBEA Test');
    expect($rendered)->toContain('+2250700000000');
});

test('new lead mail flags possible duplicates', function () {
    Lead::factory()->create(['email' => 'doublon@example.com']);
    $lead = Lead::factory()->create(['email' => 'doublon@example.com']);

    $user = User::factory()->create();
    $rendered = (string) (new NewLeadNotification($lead))->toMail($user)->render();

    expect($rendered)->toContain('Doublon possible');
});

test('new lead mail omits duplicate flag when email is unique', function () {
    $lead = Lead::factory()->create(['email' => 'unique@example.com']);

    $user = User::factory()->create();
    $rendered = (string) (new NewLeadNotification($lead))->toMail($user)->render();

    expect($rendered)->not->toContain('Doublon possible');
});

test('new lead mail skips reply-to when email is invalid', function () {
    $lead = Lead::factory()->make(['email' => 'not-an-email']);

    $user = User::factory()->create();
    $mail = (new NewLeadNotification($lead))->toMail($user);

    expect($mail->replyTo)->toBe([]);
});
