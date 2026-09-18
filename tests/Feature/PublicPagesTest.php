<?php

use App\Enums\ProjectStatus;
use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Service;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;

function fakeJpeg(): string
{
    ob_start();
    imagejpeg(imagecreatetruecolor(800, 600));

    return ob_get_clean();
}

test('renders public pages', function () {
    $sector = Sector::factory()->create(['is_active' => true, 'slug' => 'btp']);
    $expertise = Expertise::factory()->create(['is_active' => true, 'slug' => 'etudes-ingenierie']);
    $project = Project::factory()->create(['is_published' => true, 'slug' => 'projet-demo']);

    $this->get(route('home'))->assertOk();
    $this->get(route('sectors.index'))->assertOk()->assertSee($sector->name);
    $this->get(route('expertises.index'))->assertOk()->assertSee($expertise->name);
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
    $response->assertSee('images/logo-sibea.png', false);
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
    $response->assertSee('animate-marquee', false);
});

test('homepage hides testimonials without content', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Ce que disent nos clients');
});

test('serves sitemap with sectors and published projects', function () {
    Sector::factory()->create(['is_active' => true, 'slug' => 'btp']);
    Project::factory()->create(['is_published' => true, 'slug' => 'projet-demo']);
    Project::factory()->create(['is_published' => false, 'slug' => 'projet-brouillon']);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    $response->assertSee('/secteurs', false);
    $response->assertDontSee('/secteurs/btp', false);
    $response->assertSee('/realisations/projet-demo', false);
    $response->assertDontSee('projet-brouillon', false);
});

test('returns 404 for inactive or unpublished content', function () {
    Sector::factory()->create(['is_active' => false, 'slug' => 'archive', 'name' => 'Secteur archivé']);
    $expertise = Expertise::factory()->create(['is_active' => false, 'slug' => 'archive']);
    $project = Project::factory()->create(['is_published' => false, 'slug' => 'brouillon']);

    $this->get(route('sectors.index'))->assertOk()->assertDontSee('Secteur archivé');
    $this->get(route('projects.show', $project->slug))->assertNotFound();
    $this->get(route('expertises.index'))->assertOk()->assertDontSee($expertise->name);
});

test('contact page shows office details, form and map', function () {
    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Coordonnées')
        ->assertSee('Téléphone')
        ->assertSee('contact@sibea.ci')
        ->assertSee('Horaires')
        ->assertSee('Discuter sur WhatsApp')
        ->assertSee('Pays de résidence')
        ->assertSee('Territoire ciblé')
        ->assertSee('Un projet à structurer ?')
        ->assertSee('Envoyer ma demande')
        ->assertSee('Nous trouver')
        ->assertSee('Siège — Abidjan');
});

test('shows associated expertises on sector cards', function () {
    $sector = Sector::factory()->create(['name' => 'BTP', 'slug' => 'btp', 'is_active' => true, 'hero_title' => 'Construire des ouvrages qui durent.']);
    $expertise = Expertise::factory()->create(['name' => 'Gros œuvre', 'is_active' => true]);
    $sector->expertises()->attach($expertise->id);

    $this->get(route('sectors.index'))
        ->assertOk()
        ->assertSee('BTP')
        ->assertSee('Construire des ouvrages qui durent.')
        ->assertSee('Nous contacter')
        ->assertDontSee('Fiche détaillée');
});

test('shows benefits, steps and services on expertise cards', function () {
    $expertise = Expertise::factory()->create([
        'name' => 'Gros œuvre',
        'slug' => 'gros-oeuvre',
        'benefits' => ['Solidité', 'Durabilité'],
        'process_steps' => ['Étude', 'Réalisation'],
    ]);
    $service = Service::factory()->create(['name' => 'Étude de sol', 'is_active' => true]);
    $expertise->services()->attach($service->id);

    $this->get(route('expertises.index'))
        ->assertOk()
        ->assertSee('Gros œuvre')
        ->assertSee('Bénéfices')
        ->assertSee('Solidité')
        ->assertSee('Durabilité')
        ->assertSee('Étapes d’intervention')
        ->assertSee('Réalisation')
        ->assertSee('Prestations associées')
        ->assertSee('Étude de sol')
        ->assertDontSee('Fiche détaillée');
});

test('shows the realisations link only for sectors with published projects', function () {
    $withProject = Sector::factory()->create(['name' => 'BTP', 'is_active' => true]);
    $project = Project::factory()->create(['is_published' => true]);
    $project->sectors()->attach($withProject->id);

    $this->get(route('sectors.index'))
        ->assertOk()
        ->assertSee('Voir les réalisations')
        ->assertSee(route('projects.index', ['sector_id' => $withProject->id]), false);
});

test('hides the realisations link when the sector has no published project', function () {
    $sector = Sector::factory()->create(['name' => 'BTP', 'is_active' => true]);
    $draft = Project::factory()->create(['is_published' => false]);
    $draft->sectors()->attach($sector->id);

    $this->get(route('sectors.index'))
        ->assertOk()
        ->assertDontSee('Voir les réalisations');
});

test('filters projects by sector through the sector card link', function () {
    $btp = Sector::factory()->create(['name' => 'BTP', 'is_active' => true]);
    $immobilier = Sector::factory()->create(['name' => 'Immobilier', 'is_active' => true]);
    $route = Project::factory()->create(['title' => 'Route nationale', 'slug' => 'route-nationale', 'is_published' => true]);
    $residence = Project::factory()->create(['title' => 'Résidence Lagunaire', 'slug' => 'residence-lagunaire', 'is_published' => true]);
    $route->sectors()->attach($btp->id);
    $residence->sectors()->attach($immobilier->id);

    $this->get(route('sectors.index'))
        ->assertOk()
        ->assertSee(route('projects.index', ['sector_id' => $btp->id]), false);

    $this->get(route('projects.index', ['sector_id' => $btp->id]))
        ->assertOk()
        ->assertSee('Route nationale')
        ->assertSee('Réalisations du secteur')
        ->assertSee('Voir toutes les réalisations')
        ->assertDontSee('Résidence Lagunaire');
});

test('ignores an unknown sector filter on the projects index', function () {
    Project::factory()->create(['title' => 'Route nationale', 'is_published' => true]);

    $this->get(route('projects.index', ['sector_id' => 999]))
        ->assertOk()
        ->assertSee('Route nationale')
        ->assertDontSee('Réalisations du secteur');
});

test('shows project details block', function () {
    $project = Project::factory()->create([
        'slug' => 'residence-palmiers',
        'is_published' => true,
        'location' => 'Abidjan',
        'status' => ProjectStatus::Livre,
        'results' => ['24 logements', '18 mois'],
    ]);

    $this->get(route('projects.show', $project->slug))
        ->assertOk()
        ->assertSee('Abidjan')
        ->assertSee('Livré')
        ->assertSee('Résultats')
        ->assertSeeText('24 logements');
});

test('shows editorial sections, figures, testimonial and map on a project page', function () {
    Storage::fake('public');
    $project = Project::factory()->create([
        'slug' => 'residence-palmiers',
        'is_published' => true,
        'challenge' => 'Un foncier urbain contraint et un accès unique.',
        'solution' => 'Un phasage en deux tranches.',
        'impact' => '24 logements livrés avec deux mois d’avance.',
        'duration' => '18 mois',
        'surface' => '4 200 m²',
        'budget' => '2,4 milliards FCFA',
        'testimonial_quote' => 'Le phasage a sécurisé notre commercialisation.',
        'testimonial_author' => 'Directeur de promotion',
        'latitude' => 5.354,
        'longitude' => -3.9861,
        'results' => ['24 logements', '18 mois'],
    ]);
    $project->addMediaFromString(fakeJpeg())->usingFileName('chantier.jpg')->toMediaCollection('gallery');
    $project->addMediaFromString('%PDF-1.4 plaquette')->usingFileName('plaquette.pdf')->toMediaCollection('documents');

    $this->get(route('projects.show', $project->slug))
        ->assertOk()
        ->assertSee('Contexte & enjeu')
        ->assertSee('Un foncier urbain contraint et un accès unique.')
        ->assertSee('Notre réponse')
        ->assertSee('Un phasage en deux tranches.')
        ->assertSee('Impact')
        ->assertSee('24 logements livrés avec deux mois d’avance.')
        ->assertSee('4 200 m²')
        ->assertSeeText('24 logements')
        ->assertSee('2,4 milliards FCFA')
        ->assertSee('Le phasage a sécurisé notre commercialisation.')
        ->assertSee('Directeur de promotion')
        ->assertSee('Localisation')
        ->assertSee('openstreetmap.org/export/embed.html', false)
        ->assertSee('plaquette.pdf');
});

test('shows gallery, technical details and related projects on a project page', function () {
    Storage::fake('public');
    $sector = Sector::factory()->create(['name' => 'Immobilier', 'is_active' => true]);
    $project = Project::factory()->create([
        'slug' => 'residence-palmiers',
        'is_published' => true,
        'client_name' => 'Groupe Test',
        'client_publishable' => true,
    ]);
    $project->sectors()->attach($sector->id);
    $expertise = Expertise::factory()->create(['name' => 'Gros œuvre', 'is_active' => true]);
    $project->expertises()->attach($expertise->id);
    $service = Service::factory()->create(['name' => 'Étude de sol', 'is_active' => true]);
    $project->services()->attach($service->id);
    $project->addMediaFromString(fakeJpeg())->usingFileName('chantier.jpg')->toMediaCollection('gallery');

    $related = Project::factory()->create(['title' => 'Tour du Plateau', 'slug' => 'tour-du-plateau', 'is_published' => true]);
    $related->sectors()->attach($sector->id);

    $this->get(route('projects.show', $project->slug))
        ->assertOk()
        ->assertSee('Galerie')
        ->assertSee('Fiche technique')
        ->assertSee('Gros œuvre')
        ->assertSee('Étude de sol')
        ->assertSee('Groupe Test')
        ->assertSee('Réalisations similaires')
        ->assertSee('Tour du Plateau');
});

test('hides related projects when no sector is shared', function () {
    $project = Project::factory()->create(['slug' => 'projet-seul', 'is_published' => true]);
    Project::factory()->create(['title' => 'Projet sans lien', 'slug' => 'projet-sans-lien', 'is_published' => true]);

    $this->get(route('projects.show', $project->slug))
        ->assertOk()
        ->assertDontSee('Pour aller plus loin')
        ->assertDontSee('Réalisations similaires');
});

test('shows previous and next project navigation', function () {
    Project::factory()->create(['title' => 'Premier chantier', 'slug' => 'premier-chantier', 'is_published' => true]);
    $middle = Project::factory()->create(['title' => 'Chantier du milieu', 'slug' => 'chantier-du-milieu', 'is_published' => true]);
    Project::factory()->create(['title' => 'Dernier chantier', 'slug' => 'dernier-chantier', 'is_published' => true]);

    $this->get(route('projects.show', $middle->slug))
        ->assertOk()
        ->assertSee('Réalisation précédente')
        ->assertSee('Premier chantier')
        ->assertSee('Réalisation suivante')
        ->assertSee('Dernier chantier');
});
