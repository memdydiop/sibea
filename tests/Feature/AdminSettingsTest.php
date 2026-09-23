<?php

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_settings'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function settingsManager(): User
{
    $role = Role::create(['name' => 'Administrateur site', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_settings']);

    return User::factory()->create()->assignRole($role);
}

test('updates site texts', function () {
    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('texts.hero_title', 'Titre vitrine personnalisé')
        ->set('texts.contact_phone', '+225 01 02 03 04 05')
        ->call('save')
        ->assertHasNoErrors();

    expect(setting('home.hero.title'))->toBe('Titre vitrine personnalisé')
        ->and(setting('contact.phone'))->toBe('+225 01 02 03 04 05');
});

test('updates facebook url with validation', function () {
    $manager = settingsManager();

    Livewire::actingAs($manager)
        ->test('pages::admin.settings.index')
        ->set('texts.social_facebook', 'https://www.facebook.com/share/19YaSZD2sC/')
        ->call('save')
        ->assertHasNoErrors();

    expect(setting('social.facebook'))->toBe('https://www.facebook.com/share/19YaSZD2sC/');

    Livewire::actingAs($manager)
        ->test('pages::admin.settings.index')
        ->set('texts.social_facebook', 'not-a-url')
        ->call('save')
        ->assertHasErrors(['texts.social_facebook']);
});

test('updates method repeater', function () {
    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('method_steps', [['title' => 'Diagnostic', 'text' => 'Nous écoutons votre besoin.']])
        ->call('save')
        ->assertHasNoErrors();

    expect(setting_array('home.method_steps'))->toBe([['title' => 'Diagnostic', 'text' => 'Nous écoutons votre besoin.']]);
});

test('adds, moves and removes method steps', function () {
    $component = Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('method_steps', [])
        ->call('addMethodStep')
        ->set('method_steps.0.title', 'Première')
        ->call('addMethodStep')
        ->set('method_steps.1.title', 'Deuxième')
        ->call('moveMethodStep', 1, 'up');

    expect($component->get('method_steps')[0]['title'])->toBe('Deuxième');

    $component->call('removeMethodStep', 0);

    expect($component->get('method_steps'))->toHaveCount(1);
});

test('uploads a logo and a hero image', function () {
    Storage::fake('public');

    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('logo', UploadedFile::fake()->image('logo.png'))
        ->set('hero_contact', UploadedFile::fake()->image('hero.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'visuals.logo')->first()->hasMedia('file'))->toBeTrue()
        ->and(Setting::where('key', 'visuals.hero.contact')->first()->hasMedia('file'))->toBeTrue();
});

test('uploads a president photo', function () {
    Storage::fake('public');

    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('president_photo', UploadedFile::fake()->image('president.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Setting::where('key', 'visuals.president')->first()->hasMedia('file'))->toBeTrue();
});

test('removes a visual', function () {
    Storage::fake('public');

    $setting = Setting::firstOrCreate(['key' => 'visuals.logo']);
    $setting->addMediaFromString(fakeJpeg())->usingFileName('logo.png')->toMediaCollection('file');

    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->call('removeVisual', 'logo');

    expect($setting->fresh()->hasMedia('file'))->toBeFalse();
});

test('updates header labels and link visibility', function () {
    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('texts.header_projects_label', 'Nos chantiers')
        ->set('headerLinks.expertises', false)
        ->call('save')
        ->assertHasNoErrors();

    expect(setting('header.link.projects.label'))->toBe('Nos chantiers')
        ->and(setting('header.link.expertises.visible'))->toBe('0')
        ->and(setting('header.link.sectors.visible'))->toBe('1');
});

test('updates the president message', function () {
    Livewire::actingAs(settingsManager())
        ->test('pages::admin.settings.index')
        ->set('texts.president_title', 'Édito du Président')
        ->set('texts.president_name', 'Nom Prénom — Président Directeur Général')
        ->set('texts.president_text', "Chers partenaires,\n\nMerci pour votre confiance.")
        ->call('save')
        ->assertHasNoErrors();

    expect(setting('group.president.title'))->toBe('Édito du Président')
        ->and(setting('group.president.name'))->toBe('Nom Prénom — Président Directeur Général')
        ->and(setting('group.president.text'))->toBe("Chers partenaires,\n\nMerci pour votre confiance.");

    $this->get('/pages/le-groupe')
        ->assertOk()
        ->assertSee('Édito du Président')
        ->assertSee('Merci pour votre confiance.');
});

test('forbids settings admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.settings'))->assertForbidden();
});
