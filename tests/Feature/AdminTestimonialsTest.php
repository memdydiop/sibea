<?php

use App\Models\Testimonial;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_testimonials'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function testimonialManager(): User
{
    $role = Role::create(['name' => 'Éditeur témoignages', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_testimonials']);

    return User::factory()->create()->assignRole($role);
}

test('creates a testimonial', function () {
    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->set('author_name', 'Awa Koné')
        ->set('position', 'Directrice générale')
        ->set('organization', 'Groupe Ivoirien de Promotion')
        ->set('content', 'Un partenaire de projet fiable.')
        ->call('save')
        ->assertHasNoErrors();

    $testimonial = Testimonial::where('author_name', 'Awa Koné')->first();

    expect($testimonial)->not->toBeNull()
        ->and($testimonial->is_active)->toBeTrue()
        ->and($testimonial->organization)->toBe('Groupe Ivoirien de Promotion');
});

test('validates required testimonial fields', function () {
    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->call('save')
        ->assertHasErrors(['author_name' => 'required', 'content' => 'required']);
});

test('updates a testimonial', function () {
    $testimonial = Testimonial::factory()->create(['author_name' => 'Ancien nom']);

    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->call('edit', $testimonial->id)
        ->set('author_name', 'Nouveau nom')
        ->call('save')
        ->assertHasNoErrors();

    expect($testimonial->fresh()->author_name)->toBe('Nouveau nom');
});

test('toggles testimonial activation', function () {
    $testimonial = Testimonial::factory()->create(['is_active' => true]);

    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->call('toggleActive', $testimonial->id);

    expect($testimonial->fresh()->is_active)->toBeFalse();
});

test('deletes a testimonial', function () {
    $testimonial = Testimonial::factory()->create();

    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->call('delete', $testimonial->id);

    expect(Testimonial::find($testimonial->id))->toBeNull();
});

test('forbids testimonials admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.testimonials'))->assertForbidden();
});

test('searches testimonials by author and content', function () {
    Testimonial::factory()->create(['author_name' => 'Awa Recherche', 'content' => 'Un partenaire vraiment fiable pour nos chantiers.']);
    Testimonial::factory()->create(['author_name' => 'Autre Personne', 'content' => 'Un avis sans rapport.']);

    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->set('search', 'Awa Recherche')
        ->assertSee('Awa Recherche')
        ->assertDontSee('Autre Personne')
        ->set('search', 'vraiment fiable')
        ->assertSee('Awa Recherche')
        ->assertDontSee('Autre Personne');
});

test('filters testimonials by status', function () {
    Testimonial::factory()->create(['author_name' => 'Témoin Actif Test', 'content' => 'Avis actif.', 'is_active' => true]);
    Testimonial::factory()->create(['author_name' => 'Témoin Inactif Test', 'content' => 'Avis inactif.', 'is_active' => false]);

    Livewire::actingAs(testimonialManager())
        ->test('pages::admin.testimonials.index')
        ->set('statusFilter', 'active')
        ->assertSee('Témoin Actif Test')
        ->assertDontSee('Témoin Inactif Test')
        ->set('statusFilter', 'inactive')
        ->assertSee('Témoin Inactif Test')
        ->assertDontSee('Témoin Actif Test');
});
