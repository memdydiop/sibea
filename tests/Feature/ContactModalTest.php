<?php

use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\NewLeadNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

function validContactData(Sector $sector): array
{
    return [
        'name' => 'Awa Koné',
        'company' => 'SIBEA Test',
        'email' => 'awa@example.com',
        'phone' => '+2250700000000',
        'sector_id' => $sector->id,
        'request_type' => 'devis',
        'budget' => '10M FCFA',
        'message' => 'Je souhaite un devis pour un projet.',
        'consent' => true,
    ];
}

test('creates a lead and shows success on valid submit', function () {
    Event::fake([LeadCreated::class]);
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->call('submit')
        ->assertHasNoErrors()
        ->assertSet('success', true);

    $this->assertDatabaseHas('leads', [
        'email' => 'awa@example.com',
        'sector_id' => $sector->id,
        'status' => 'nouveau',
        'source' => 'site',
    ]);

    Event::assertDispatched(LeadCreated::class);
});

test('records consent timestamp and ip', function () {
    Event::fake([LeadCreated::class]);
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->call('submit')
        ->assertSet('success', true);

    $lead = Lead::where('email', 'awa@example.com')->first();

    expect($lead->consent_at)->not->toBeNull()
        ->and($lead->consent_ip)->not->toBeNull();
});

test('ignores silently when honeypot is filled', function () {
    Event::fake([LeadCreated::class]);
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->set('honeypot', 'spam-bot')
        ->call('submit')
        ->assertSet('success', false);

    $this->assertDatabaseCount('leads', 0);
    Event::assertNotDispatched(LeadCreated::class);
});

test('refuses submit for an inactive sector', function () {
    $sector = Sector::factory()->create(['is_active' => false]);

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->call('submit')
        ->assertHasErrors(['sector_id']);

    $this->assertDatabaseCount('leads', 0);
});

test('refuses submit without consent', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->set('consent', false)
        ->call('submit')
        ->assertHasErrors(['consent']);

    $this->assertDatabaseCount('leads', 0);
});

test('throttles contact submits after 5 attempts', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    for ($i = 0; $i < 5; $i++) {
        Livewire::test('contact-modal')
            ->set(validContactData($sector))
            ->set('email', "user{$i}@example.com")
            ->call('submit')
            ->assertSet('success', true);
    }

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->set('email', 'blocked@example.com')
        ->call('submit')
        ->assertHasErrors(['email']);

    $this->assertDatabaseMissing('leads', ['email' => 'blocked@example.com']);
});

test('notifies commercial on new lead', function () {
    Notification::fake();
    Permission::create(['name' => 'manage_leads', 'guard_name' => 'web']);
    $role = Role::create(['name' => 'Commercial notif', 'guard_name' => 'web']);
    $role->givePermissionTo('manage_leads');
    $commercial = User::factory()->create()->assignRole($role);
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::test('contact-modal')
        ->set(validContactData($sector))
        ->call('submit')
        ->assertSet('success', true);

    Notification::assertSentTo($commercial, NewLeadNotification::class);
});
