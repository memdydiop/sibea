<?php

use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (['view_dashboard', 'manage_pages'] as $name) {
        Permission::create(['name' => $name, 'guard_name' => 'web']);
    }
});

function pagesManager(): User
{
    $role = Role::create(['name' => 'Éditeur pages', 'guard_name' => 'web']);
    $role->givePermissionTo(['view_dashboard', 'manage_pages']);

    return User::factory()->create()->assignRole($role);
}

test('creates a page with a generated slug', function () {
    Livewire::actingAs(pagesManager())
        ->test('pages::admin.pages.index')
        ->set('title', 'À propos')
        ->set('content', "## Notre histoire\n\nDepuis 2010.")
        ->call('save')
        ->assertHasNoErrors();

    $page = Page::where('slug', 'a-propos')->first();

    expect($page)->not->toBeNull()
        ->and($page->is_published)->toBeTrue()
        ->and($page->content)->toContain('Notre histoire');
});

test('updates a page', function () {
    $page = Page::factory()->create(['title' => 'Ancien titre']);

    Livewire::actingAs(pagesManager())
        ->test('pages::admin.pages.index')
        ->call('edit', $page->id)
        ->set('title', 'Nouveau titre')
        ->call('save')
        ->assertHasNoErrors();

    expect($page->fresh()->title)->toBe('Nouveau titre');
});

test('toggles page publication', function () {
    $page = Page::factory()->create(['is_published' => true]);

    Livewire::actingAs(pagesManager())
        ->test('pages::admin.pages.index')
        ->call('togglePublish', $page->id);

    expect($page->fresh()->is_published)->toBeFalse();
});

test('deletes a page', function () {
    $page = Page::factory()->create();

    Livewire::actingAs(pagesManager())
        ->test('pages::admin.pages.index')
        ->call('delete', $page->id);

    expect(Page::find($page->id))->toBeNull();
});

test('forbids pages admin without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.pages'))->assertForbidden();
});

test('filters pages by publication status', function () {
    Page::factory()->create(['title' => 'Page Publiee Vitrine', 'is_published' => true]);
    Page::factory()->create(['title' => 'Page Brouillon Atelier', 'is_published' => false]);

    Livewire::actingAs(pagesManager())
        ->test('pages::admin.pages.index')
        ->set('statusFilter', 'published')
        ->assertSee('Page Publiee Vitrine')
        ->assertDontSee('Page Brouillon Atelier')
        ->set('statusFilter', 'draft')
        ->assertSee('Page Brouillon Atelier')
        ->assertDontSee('Page Publiee Vitrine');
});

test('searches pages by title', function () {
    Page::factory()->create(['title' => 'Mentions Legales du Site']);
    Page::factory()->create(['title' => 'Charte Graphique Interne']);

    Livewire::actingAs(pagesManager())
        ->test('pages::admin.pages.index')
        ->set('search', 'Mentions')
        ->assertSee('Mentions Legales du Site')
        ->assertDontSee('Charte Graphique Interne');
});
