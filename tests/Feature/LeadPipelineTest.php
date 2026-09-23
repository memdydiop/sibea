<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\ProspectType;
use App\Models\Expertise;
use App\Models\Lead;
use App\Models\Sector;
use App\Models\User;
use App\Support\LeadAssigner;
use App\Support\SlaClock;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_leads'] as $name) {
        Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
    }
});

function pipelineManager(): User
{
    $role = Role::firstOrCreate(['name' => 'Pipeline manager', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_leads']);

    return User::factory()->create()->assignRole($role);
}

test('first contact timestamp is set on first exit from nouveau', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Nouveau]);

    expect($lead->first_contacted_at)->toBeNull();

    $lead->update(['status' => LeadStatus::Qualification]);

    expect($lead->fresh()->first_contacted_at)->not->toBeNull();
});

test('first contact timestamp is kept on later transitions', function () {
    $lead = Lead::factory()->create(['status' => LeadStatus::Nouveau]);
    $lead->update(['status' => LeadStatus::Qualification]);
    $first = $lead->fresh()->first_contacted_at;

    $lead->update(['status' => LeadStatus::Rdv]);

    expect($lead->fresh()->first_contacted_at?->equalTo($first))->toBeTrue();
});

test('response time helpers measure sla', function () {
    $lead = Lead::factory()->create([
        'status' => LeadStatus::Nouveau,
        'created_at' => now()->subHours(3),
    ]);

    expect($lead->responseTimeInMinutes())->toBeNull()
        ->and($lead->respondedWithinHours())->toBeNull();

    $lead->update(['status' => LeadStatus::Qualification]);

    $fresh = $lead->fresh();

    expect($fresh->responseTimeInMinutes())->toBeGreaterThanOrEqual(170)
        ->and($fresh->respondedWithinHours())->toBeTrue()
        ->and($fresh->respondedWithinHours(1))->toBeFalse();
});

test('contact form stores origin page', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::withHeaders(['referer' => 'https://sibea.ci/secteurs/btp'])
        ->test('pages::contact')
        ->set([
            'name' => 'Jean Kouassi',
            'company' => 'ABC Construction',
            'email' => 'jean@example.com',
            'phone' => '+2250700000000',
            'residence_country' => 'Côte d’Ivoire',
            'target_territory' => 'Abidjan',
            'sector_id' => $sector->id,
            'request_type' => 'devis',
            'budget' => '85 000 000 FCFA',
            'message' => 'Construction immeuble R+5.',
        ])
        ->call('submit')
        ->assertHasNoErrors();

    expect(Lead::where('email', 'jean@example.com')->first()->origin_page)->toBe('/secteurs/btp');
});

test('contact form resolves expertise from origin page', function () {
    $sector = Sector::factory()->create(['is_active' => true]);
    $expertise = Expertise::factory()->create(['slug' => 'construction-realisation']);

    Livewire::withHeaders(['referer' => 'https://sibea.ci/expertises/construction-realisation'])
        ->test('pages::contact')
        ->set([
            'name' => 'Awa Diallo',
            'email' => 'awa-pipeline@example.com',
            'residence_country' => 'Côte d’Ivoire',
            'target_territory' => 'Abidjan',
            'sector_id' => $sector->id,
            'request_type' => 'devis',
            'message' => 'Besoin construction.',
        ])
        ->call('submit')
        ->assertHasNoErrors();

    expect(Lead::where('email', 'awa-pipeline@example.com')->first()->expertise_id)->toBe($expertise->id);
});

test('admin can qualify expertise and whatsapp source', function () {
    $manager = pipelineManager();
    $sector = Sector::factory()->create();
    $expertise = Expertise::factory()->create();

    $component = Livewire::actingAs($manager)
        ->test('pages::admin.leads.index')
        ->set('name', 'WhatsApp Prospect')
        ->set('email', 'wa@example.com')
        ->set('sector_id', $sector->id)
        ->set('expertise_id', $expertise->id)
        ->set('request_type', 'information')
        ->set('message', 'Via WhatsApp.')
        ->set('source', LeadSource::Whatsapp->value)
        ->set('origin_page', '/contact')
        ->call('store')
        ->assertHasNoErrors();

    $lead = Lead::where('email', 'wa@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->source)->toBe(LeadSource::Whatsapp)
        ->and($lead->expertise_id)->toBe($expertise->id)
        ->and($lead->origin_page)->toBe('/contact');
});

test('lead gets a readable reference on creation', function () {
    $lead = Lead::factory()->create();

    expect($lead->fresh()->reference)->toBe('SIB-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT));
});

test('admin can set next action and estimated amount', function () {
    $manager = pipelineManager();
    $lead = Lead::factory()->create(['status' => LeadStatus::Qualification]);

    Livewire::actingAs($manager)
        ->test('pages::admin.leads.index')
        ->call('edit', $lead->id)
        ->set('status', LeadStatus::Proposition->value)
        ->set('next_action', 'Relance')
        ->set('next_action_at', now()->addDays(5)->format('Y-m-d'))
        ->set('estimated_amount', 85000000)
        ->call('save')
        ->assertHasNoErrors();

    $fresh = $lead->fresh();

    expect($fresh->next_action)->toBe('Relance')
        ->and($fresh->estimated_amount)->toBe(85000000)
        ->and($fresh->next_action_at)->not->toBeNull();
});

test('detects overdue next actions on open deals only', function () {
    $open = Lead::factory()->create([
        'status' => LeadStatus::Proposition,
        'next_action' => 'Relance',
        'next_action_at' => now()->subDay(),
    ]);
    $won = Lead::factory()->create([
        'status' => LeadStatus::Gagne,
        'next_action_at' => now()->subDay(),
    ]);

    expect($open->isNextActionOverdue())->toBeTrue()
        ->and($won->isNextActionOverdue())->toBeFalse();
});

test('dashboard pilotage displays sla and conversion', function () {
    $manager = pipelineManager();

    Lead::factory()->create([
        'status' => LeadStatus::Nouveau,
        'created_at' => now()->subHours(2),
        'first_contacted_at' => null,
    ]);
    $fast = Lead::factory()->create([
        'status' => LeadStatus::Nouveau,
        'created_at' => now()->subHours(5),
    ]);
    $fast->update(['status' => LeadStatus::Qualification]);
    $fast->fresh()->update(['first_contacted_at' => $fast->created_at->copy()->addHours(3)]);
    Lead::factory()->create(['status' => LeadStatus::Gagne]);

    $this->actingAs($manager)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Pilotage commercial')
        ->assertSee('Temps moyen')
        ->assertSee('Prospects reçus')
        ->assertSee('Taux de conversion');
});

test('contact form stores prospect type and deadline with auto assignment', function () {
    $sector = Sector::factory()->create(['is_active' => true]);
    $commercial = pipelineManager();
    config()->set('leads.auto_assign', true);

    Livewire::test('pages::contact')
        ->set([
            'name' => 'Entreprise Test',
            'company' => 'ABC Construction',
            'prospect_type' => 'entreprise',
            'email' => 'b2b-auto@example.com',
            'residence_country' => 'Côte d’Ivoire',
            'target_territory' => 'Abidjan',
            'sector_id' => $sector->id,
            'request_type' => 'devis',
            'deadline' => now()->addMonths(3)->format('Y-m-d'),
            'message' => 'Immeuble R+5.',
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $lead = Lead::where('email', 'b2b-auto@example.com')->first();

    expect($lead)->not->toBeNull()
        ->and($lead->prospect_type->value)->toBe('entreprise')
        ->and($lead->deadline)->not->toBeNull()
        ->and($lead->assigned_to)->toBe($commercial->id);
});

test('auto assignment goes to the least loaded commercial', function () {
    $managerA = pipelineManager();
    $managerB = pipelineManager();

    Lead::factory()->create(['status' => LeadStatus::Qualification, 'assigned_to' => $managerA->id]);
    Lead::factory()->create(['status' => LeadStatus::Qualification, 'assigned_to' => $managerA->id]);

    expect(LeadAssigner::leastLoaded()?->id)->toBe($managerB->id);
});

test('admin can filter by prospect type', function () {
    Lead::factory()->create(['email' => 'b2b@example.com', 'prospect_type' => ProspectType::Entreprise]);
    Lead::factory()->create(['email' => 'b2c@example.com', 'prospect_type' => ProspectType::Particulier]);

    Livewire::actingAs(pipelineManager())
        ->test('pages::admin.leads.index')
        ->set('typeFilter', 'entreprise')
        ->assertSee('b2b@example.com')
        ->assertDontSee('b2c@example.com');
});

test('contact form captures utm from referer', function () {
    $sector = Sector::factory()->create(['is_active' => true]);

    Livewire::withHeaders(['referer' => 'https://sibea.ci/secteurs/btp?utm_source=facebook&utm_medium=cpc&utm_campaign=rentree'])
        ->test('pages::contact')
        ->set([
            'name' => 'UTM Test',
            'email' => 'utm@example.com',
            'prospect_type' => 'particulier',
            'residence_country' => 'Côte d’Ivoire',
            'target_territory' => 'Abidjan',
            'sector_id' => $sector->id,
            'request_type' => 'information',
            'message' => 'Via campagne.',
        ])
        ->call('submit')
        ->assertHasNoErrors();

    $lead = Lead::where('email', 'utm@example.com')->first();

    expect($lead->utm_source)->toBe('facebook')
        ->and($lead->utm_medium)->toBe('cpc')
        ->and($lead->utm_campaign)->toBe('rentree')
        ->and($lead->origin_page)->toBe('/secteurs/btp');
});

test('sla excludes sundays', function () {
    // Samedi 10h → deadline lundi 10h (dimanche sauté).
    $saturday = Carbon::create(2026, 9, 26, 10, 0, 0);
    $deadline = SlaClock::addWorkingHours($saturday, 24);

    expect($deadline->format('Y-m-d H:i'))->toBe('2026-09-28 10:00');

    // Samedi 10h → lundi 10h = 24h ouvrées.
    $monday = Carbon::create(2026, 9, 28, 10, 0, 0);
    expect(SlaClock::workingMinutesBetween($saturday, $monday))->toBe(24 * 60);
});

test('sunday lead is not breached on monday morning', function () {
    $sunday = now()->previous(Carbon::SUNDAY)->setTime(10, 0);
    $lead = Lead::factory()->create([
        'status' => LeadStatus::Nouveau,
        'created_at' => $sunday,
        'first_contacted_at' => null,
    ]);

    // Deadline = mardi 00h → lundi 10h encore dans les temps.
    $mondayMorning = $sunday->copy()->next(Carbon::MONDAY)->setTime(10, 0);
    $this->travelTo($mondayMorning);

    expect($lead->fresh()->isSlaBreached())->toBeFalse();
});

test('round robin rotates strictly', function () {
    config()->set('leads.assign_mode', 'round_robin');
    $managerA = pipelineManager();
    $managerB = pipelineManager();
    $managerC = pipelineManager();

    expect(LeadAssigner::roundRobin()?->id)->toBe($managerA->id);

    Lead::factory()->create(['assigned_to' => $managerA->id]);
    expect(LeadAssigner::roundRobin()?->id)->toBe($managerB->id);

    Lead::factory()->create(['assigned_to' => $managerB->id]);
    expect(LeadAssigner::roundRobin()?->id)->toBe($managerC->id);

    Lead::factory()->create(['assigned_to' => $managerC->id]);
    expect(LeadAssigner::roundRobin()?->id)->toBe($managerA->id);
});

test('assign mode least loaded still available', function () {
    config()->set('leads.assign_mode', 'least_loaded');
    $managerA = pipelineManager();
    $managerB = pipelineManager();

    Lead::factory()->create(['status' => LeadStatus::Qualification, 'assigned_to' => $managerA->id]);

    expect(LeadAssigner::next()?->id)->toBe($managerB->id);
});
