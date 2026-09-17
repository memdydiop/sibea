<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Sector;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_sectors', 'manage_leads'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function leadsManager(): User
{
    $role = Role::create(['name' => 'Gestionnaire leads', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_leads']);

    return User::factory()->create()->assignRole($role);
}

test('forbids leads admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.leads'))->assertForbidden();
});

test('updates lead status and assignment', function () {
    $manager = leadsManager();
    $assignee = User::factory()->create();
    $lead = Lead::factory()->create(['status' => LeadStatus::Nouveau]);

    Livewire::actingAs($manager)
        ->test('pages::admin.leads.index')
        ->call('edit', $lead->id)
        ->set('status', LeadStatus::Qualifie->value)
        ->set('assigned_to', $assignee->id)
        ->set('notes', 'Prospect sérieux.')
        ->call('save')
        ->assertHasNoErrors();

    expect($lead->fresh())
        ->status->toBe(LeadStatus::Qualifie)
        ->assigned_to->toBe($assignee->id)
        ->notes->toBe('Prospect sérieux.');
});

test('deletes a lead', function () {
    $lead = Lead::factory()->create();

    Livewire::actingAs(leadsManager())
        ->test('pages::admin.leads.index')
        ->call('delete', $lead->id);

    $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
});

test('filters leads by status', function () {
    Lead::factory()->create(['email' => 'nouveau@example.com', 'status' => LeadStatus::Nouveau]);
    Lead::factory()->create(['email' => 'qualifie@example.com', 'status' => LeadStatus::Qualifie]);

    Livewire::actingAs(leadsManager())
        ->test('pages::admin.leads.index')
        ->set('statusFilter', LeadStatus::Qualifie->value)
        ->assertSee('qualifie@example.com')
        ->assertDontSee('nouveau@example.com');
});

test('purges leads older than 3 years', function () {
    $old = Lead::factory()->create(['email' => 'vieux@example.com', 'created_at' => now()->subYears(4)]);
    $recent = Lead::factory()->create(['email' => 'recent@example.com']);

    Livewire::actingAs(leadsManager())
        ->test('pages::admin.leads.index')
        ->call('purge');

    $this->assertDatabaseMissing('leads', ['id' => $old->id]);
    $this->assertDatabaseHas('leads', ['id' => $recent->id]);
});

test('lists lead sector name', function () {
    $sector = Sector::factory()->create(['name' => 'Voirie']);
    Lead::factory()->create(['sector_id' => $sector->id, 'email' => 'avec-secteur@example.com']);

    Livewire::actingAs(leadsManager())
        ->test('pages::admin.leads.index')
        ->assertSee('Voirie');
});
