<?php

use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Testimonial;

test('renders public pages', function () {
    $sector = Sector::factory()->create(['is_active' => true, 'slug' => 'btp']);
    $expertise = Expertise::factory()->create(['is_active' => true, 'slug' => 'etudes-ingenierie']);
    $project = Project::factory()->create(['is_published' => true, 'slug' => 'projet-demo']);

    $this->get(route('home'))->assertOk();
    $this->get(route('sectors.index'))->assertOk();
    $this->get(route('sectors.show', $sector->slug))->assertOk();
    $this->get(route('expertises.index'))->assertOk();
    $this->get(route('expertises.show', $expertise->slug))->assertOk();
    $this->get(route('projects.index'))->assertOk();
    $this->get(route('projects.show', $project->slug))->assertOk();
    $this->get(route('contact'))->assertOk();
    $this->get(route('legal'))->assertOk();
    $this->get(route('privacy'))->assertOk();
});

test('homepage shows bigness-style sections', function () {
    Sector::factory()->create(['is_active' => true, 'slug' => 'btp', 'hero_title' => 'Titre Hero Slide', 'hero_cta_label' => 'Voir le BTP']);
    Project::factory()->create(['is_published' => true, 'title' => 'Projet Vitrine', 'slug' => 'projet-vitrine']);
    Testimonial::factory()->create(['author_name' => 'Client Témoin', 'is_active' => true]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Des expertises solides pour construire des projets durables.');
    $response->assertSee('images/logo-sibea.jpeg', false);
    $response->assertSee('Titre Hero Slide');
    $response->assertSee('Voir le BTP');
    $response->assertSee('NOS SECTEURS');
    $response->assertSee('NOS EXPERTISES');
    $response->assertSee('RÉALISATIONS');
    $response->assertSee('Projet Vitrine');
    $response->assertSee('NOTRE MÉTHODE');
    $response->assertSee('Ce que disent nos clients');
    $response->assertSee('Client Témoin');
    $response->assertSee('Vous avez un projet à structurer ?');
    $response->assertSee('aria-label="Menu"', false);
});

test('homepage hides testimonials without content', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Ce que disent nos clients');
});

test('redirects legacy sector urls with 301', function () {
    $this->get('/btp')->assertRedirect('/secteurs/btp', 301);
    $this->get('/immobilier')->assertRedirect('/secteurs/immobilier', 301);
    $this->get('/energie')->assertRedirect('/secteurs/energie', 301);
    $this->get('/agroalimentaire')->assertRedirect('/secteurs/agro-industrie', 301);
    $this->get('/secteurs/agroalimentaire')->assertRedirect('/secteurs/agro-industrie', 301);
    $this->get('/agro-industrie')->assertRedirect('/secteurs/agro-industrie', 301);
});

test('serves sitemap with sectors and published projects', function () {
    Sector::factory()->create(['is_active' => true, 'slug' => 'btp']);
    Project::factory()->create(['is_published' => true, 'slug' => 'projet-demo']);
    Project::factory()->create(['is_published' => false, 'slug' => 'projet-brouillon']);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    $response->assertSee('/secteurs/btp', false);
    $response->assertSee('/realisations/projet-demo', false);
    $response->assertDontSee('projet-brouillon', false);
});

test('returns 404 for inactive or unpublished content', function () {
    $sector = Sector::factory()->create(['is_active' => false, 'slug' => 'archive']);
    $expertise = Expertise::factory()->create(['is_active' => false, 'slug' => 'archive']);
    $project = Project::factory()->create(['is_published' => false, 'slug' => 'brouillon']);

    $this->get(route('sectors.show', $sector->slug))->assertNotFound();
    $this->get(route('expertises.show', $expertise->slug))->assertNotFound();
    $this->get(route('projects.show', $project->slug))->assertNotFound();
});
