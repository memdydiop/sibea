<?php

use App\Models\Project;
use App\Models\Sector;

test('sector pages expose their own seo title and description', function () {
    $sector = Sector::factory()->create([
        'name' => 'BTP',
        'slug' => 'btp',
        'short_description' => 'Bâtiments et travaux publics.',
        'description' => 'Notre expertise BTP complète, du terrassement à la livraison.',
    ]);

    $this->get(route('sectors.show', $sector->slug))
        ->assertOk()
        ->assertSee('<title>BTP — GROUPE SIBEA</title>', false)
        ->assertSee('name="description" content="Notre expertise BTP complète, du terrassement à la livraison."', false);
});

test('sector meta description falls back to the short description', function () {
    $sector = Sector::factory()->create([
        'name' => 'Énergie',
        'slug' => 'energie',
        'short_description' => 'Solutions énergétiques durables.',
        'description' => null,
    ]);

    $this->get(route('sectors.show', $sector->slug))
        ->assertOk()
        ->assertSee('name="description" content="Solutions énergétiques durables."', false);
});

test('project pages expose their own seo title and description', function () {
    $project = Project::factory()->create([
        'title' => 'Rizerie moderne',
        'slug' => 'rizerie-moderne',
        'short_description' => 'Une rizerie clé en main.',
        'is_published' => true,
    ]);

    $this->get(route('projects.show', $project->slug))
        ->assertOk()
        ->assertSee('<title>Rizerie moderne — GROUPE SIBEA</title>', false)
        ->assertSee('name="description" content="Une rizerie clé en main."', false);
});

test('legacy sector urls redirect permanently to the canonical urls', function () {
    $this->get('/btp')->assertRedirect('/secteurs/btp', 301);
    $this->get('/agroalimentaire')->assertRedirect('/secteurs/agro-industrie', 301);
    $this->get('/secteurs/agroalimentaire')->assertRedirect('/secteurs/agro-industrie', 301);
});

test('robots.txt blocks the admin area and exposes the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('Disallow: /admin', false);
});
