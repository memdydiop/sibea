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
