<?php

use App\Enums\ProjectStatus;
use App\Models\Expertise;
use App\Models\Page;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Testimonial;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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

test('home hero is a static banner without carousel', function () {
    Sector::factory()->create(['name' => 'BTP', 'hero_title' => 'Titre secteur', 'is_active' => true]);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Bâtir, valoriser et alimenter l’avenir de nos territoires.')
        ->assertSee('Groupe multisectoriel intégré, nous unissons nos expertises pour des projets durables à fort impact local.')
        ->assertSee('Découvrir nos secteurs')
        ->assertDontSee('Titre secteur');
});

test('shows custom settings on the public site', function () {
    Setting::put('home.hero.title', 'Titre vitrine personnalisé');
    Setting::put('general.footer_tagline', 'Baseline personnalisée');
    Setting::put('contact.phone', '+225 01 02 03 04 05');

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('Titre vitrine personnalisé')
        ->assertSee('Baseline personnalisée');

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('+225 01 02 03 04 05');
});

test('renders header labels and respects link visibility', function () {
    Setting::put('header.link.projects.label', 'Nos chantiers');
    Setting::put('header.login_label', 'Espace admin');

    $this->get(route('contact'))
        ->assertOk()
        ->assertSee('Nos chantiers')
        ->assertSee('Espace admin');

    Setting::put('header.link.projects.visible', '0');
    Setting::put('header.link.projects.label', 'Chantiers cachés');

    $this->get(route('contact'))
        ->assertOk()
        ->assertDontSee('Chantiers cachés');
});

test('renders editorial pages from the database', function () {
    Page::create([
        'slug' => 'mentions-legales',
        'title' => 'Mentions personnalisées',
        'content' => "## Éditeur\n\nContenu personnalisé.",
        'is_published' => true,
    ]);

    $this->get(route('legal'))
        ->assertOk()
        ->assertSee('Mentions personnalisées')
        ->assertSee('Contenu personnalisé.');
});

test('renders free editorial pages and lists them in the sitemap', function () {
    Page::create([
        'slug' => 'le-groupe',
        'title' => 'Le Groupe',
        'content' => "## Notre vision\n\nUn groupe multi-activités.",
        'is_published' => true,
    ]);

    $this->get('/pages/le-groupe')
        ->assertOk()
        ->assertSee('Le Groupe')
        ->assertSee('Un groupe multi-activités.');

    $this->get(route('sitemap'))
        ->assertOk()
        ->assertSee('/pages/le-groupe', false);
});

test('keeps reserved editorial pages on their dedicated urls', function () {
    Page::create([
        'slug' => 'mentions-legales',
        'title' => 'Mentions',
        'content' => 'x',
        'is_published' => true,
    ]);

    $this->get('/pages/mentions-legales')->assertNotFound();
    $this->get(route('legal'))->assertOk()->assertSee('Mentions');
});

test('returns 404 for unpublished or unknown editorial pages', function () {
    Page::create([
        'slug' => 'mentions-legales',
        'title' => 'Brouillon',
        'content' => 'x',
        'is_published' => false,
    ]);

    $this->get(route('legal'))->assertNotFound();
    $this->get('/pages/inconnue')->assertNotFound();
});

test('homepage shows bigness-style sections', function () {
    Sector::factory()->create(['is_active' => true, 'slug' => 'btp']);
    Project::factory()->create(['is_published' => true, 'title' => 'Projet Vitrine', 'slug' => 'projet-vitrine']);
    Testimonial::factory()->create(['author_name' => 'Client Témoin', 'is_active' => true]);

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('Bâtir, valoriser et alimenter l’avenir de nos territoires.');
    $response->assertSee('Groupe multisectoriel intégré, nous unissons nos expertises pour des projets durables à fort impact local.');
    $response->assertSee('Un impact durable, au-delà de la performance technique.');
    $response->assertSee('images/logo-sibea.png', false);
    $response->assertSee('NOS SECTEURS');
    $response->assertSee('NOS EXPERTISES');
    $response->assertSee('RÉALISATIONS');
    $response->assertSee('Projet Vitrine');
    $response->assertSee('NOTRE MÉTHODE');
    $response->assertSee('Ce que disent nos clients');
    $response->assertSee('Client Témoin');
    $response->assertSee('Donnons vie à vos projets d’envergure.');
    $response->assertSee('aria-label="Menu"', false);
});

test('homepage hides testimonials without content', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee('Ce que disent nos clients');
});

test('serves robots.txt with the sitemap url', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

test('serves sitemap with sectors and published projects', function () {
    Sector::factory()->create(['is_active' => true, 'slug' => 'btp']);
    Project::factory()->create(['is_published' => true, 'slug' => 'projet-demo']);
    Project::factory()->create(['is_published' => false, 'slug' => 'projet-brouillon']);

    $response = $this->get(route('sitemap'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');
    $response->assertSee('/secteurs', false);
    $response->assertSee('/secteurs/btp', false);
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

test('shows services on expertise cards and details in a modal', function () {
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
        ->assertSee('Bénéfices & étapes')
        ->assertSee('Étude de sol')
        ->assertDontSee('Solidité');

    Livewire::test('pages::expertises.index')
        ->call('openDetails', $expertise->id)
        ->assertSet('showDetails', true)
        ->assertSee('Solidité')
        ->assertSee('Durabilité')
        ->assertSee('Étapes d’intervention')
        ->assertSee('Réalisation');
});

test('links sector cards to their dedicated page', function () {
    $sector = Sector::factory()->create(['name' => 'BTP', 'slug' => 'btp', 'is_active' => true]);

    $this->get(route('sectors.index'))
        ->assertOk()
        ->assertSee('Découvrir le secteur')
        ->assertSee(route('sectors.show', $sector->slug), false);
});

test('renders a sector page with its editorial content', function () {
    $sector = Sector::factory()->create([
        'name' => 'BTP',
        'slug' => 'btp',
        'is_active' => true,
        'hero_title' => 'Construire des ouvrages qui durent.',
        'page_intro_title' => 'Une expertise globale.',
        'page_intro_text' => 'Nous accompagnons les acteurs publics et privés.',
        'page_cards' => [
            ['title' => 'Génie civil', 'text' => 'Structures complexes.'],
            ['title' => 'VRD', 'text' => 'Voirie et réseaux divers.'],
        ],
        'page_cta_title' => 'Un projet en vue ?',
        'page_cta_text' => 'Nos ingénieurs étudient votre cahier des charges.',
        'page_cta_label' => 'Solliciter une étude technique',
    ]);

    $this->get(route('sectors.show', $sector->slug))
        ->assertOk()
        ->assertSee('Construire des ouvrages qui durent.')
        ->assertSee('Une expertise globale.')
        ->assertSee('Génie civil')
        ->assertSee('VRD')
        ->assertSee('Un projet en vue ?')
        ->assertSee('Solliciter une étude technique');
});

test('returns 404 for an inactive sector page', function () {
    $sector = Sector::factory()->create(['slug' => 'archive', 'is_active' => false]);

    $this->get(route('sectors.show', $sector->slug))->assertNotFound();
});

test('filters projects by sector on the projects index', function () {
    $btp = Sector::factory()->create(['name' => 'BTP', 'is_active' => true]);
    Project::factory()->create(['title' => 'Route nationale', 'slug' => 'route-nationale', 'is_published' => true])->sectors()->attach($btp->id);
    Project::factory()->create(['title' => 'Résidence Lagunaire', 'slug' => 'residence-lagunaire', 'is_published' => true]);

    $this->get(route('projects.index', ['sector_id' => $btp->id]))
        ->assertOk()
        ->assertSee('Route nationale')
        ->assertSee('Réalisations du secteur')
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
