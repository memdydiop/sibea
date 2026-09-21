<?php

use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function contactPageData(Sector $sector): array
{
    return [
        'name' => 'Awa Koné',
        'company' => 'SIBEA Test',
        'email' => 'awa@example.com',
        'phone' => '+2250700000000',
        'residence_country' => 'France',
        'target_territory' => 'Abidjan, Cocody',
        'sector_id' => $sector->id,
        'request_type' => 'devis',
        'budget' => '10M FCFA',
        'message' => 'Je souhaite un devis pour un projet.',
    ];
}

test('creates a lead with implicit consent data and history', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('pages::contact')
        ->set(contactPageData($sector))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('success', true);

    $lead = Lead::where('email', 'awa@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->status->value)->toBe('nouveau')
        ->and($lead->source->value)->toBe('site')
        ->and($lead->residence_country)->toBe('France')
        ->and($lead->target_territory)->toBe('Abidjan, Cocody')
        ->and($lead->consent_at)->not->toBeNull()
        ->and($lead->consent_ip)->not->toBeNull()
        ->and($lead->activities()->where('action', 'created')->exists())->toBeTrue();
});

test('ignores silently when honeypot is filled', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('pages::contact')
        ->set(contactPageData($sector))
        ->set('honeypot', 'spam-bot')
        ->call('submit')
        ->assertSet('success', false);

    $this->assertDatabaseCount('leads', 0);
});

test('refuses submit for an inactive sector', function () {
    $sector = Sector::factory()->create(['is_active' => false]);

    Livewire::test('pages::contact')
        ->set(contactPageData($sector))
        ->call('submit')
        ->assertHasErrors(['sector_id']);

    $this->assertDatabaseCount('leads', 0);
});

test('throttles contact submits after 5 attempts', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test('pages::contact')
            ->set(contactPageData($sector))
            ->set('email', "user{$i}@example.com")
            ->call('submit')
            ->assertSet('success', true);
    }

    Livewire::test('pages::contact')
        ->set(contactPageData($sector))
        ->set('email', 'blocked@example.com')
        ->call('submit')
        ->assertHasErrors(['email']);

    $this->assertDatabaseMissing('leads', ['email' => 'blocked@example.com']);
});

test('routes unassigned lead to the shared mailbox', function () {
    Notification::fake();
    config()->set('leads.notification_email', 'contact@sibea.ci');
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('pages::contact')
        ->set(contactPageData($sector))
        ->call('submit')
        ->assertSet('success', true);

    Notification::assertSentOnDemand(NewLeadNotification::class);
});

test('duplicate lead events notify only once', function () {
    Notification::fake();
    config()->set('leads.notification_email', 'contact@sibea.ci');
    $lead = Lead::factory()->create();

    Event::dispatch(new LeadCreated($lead));
    Event::dispatch(new LeadCreated($lead));

    Notification::assertSentOnDemandTimes(NewLeadNotification::class, 1);
});

test('notifies the assignee when the lead is assigned', function () {
    Notification::fake();
    $commercial = User::factory()->create();
    $lead = Lead::factory()->create(['assigned_to' => $commercial->id]);

    Event::dispatch(new LeadCreated($lead));

    Notification::assertSentTo($commercial, NewLeadNotification::class);
    Notification::assertSentOnDemandTimes(NewLeadNotification::class, 0);
});
