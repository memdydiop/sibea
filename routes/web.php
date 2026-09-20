<?php

use App\Models\Page;
use App\Models\Project;
use App\Models\Sector;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

// Redirects 301 (compat SEO, CDC v1.3) — avant les routes Livewire pour priorité de matching
Route::redirect('/btp', '/secteurs/btp', 301);
Route::redirect('/immobilier', '/secteurs/immobilier', 301);
Route::redirect('/energie', '/secteurs/energie', 301);
Route::redirect('/agroalimentaire', '/secteurs/agro-industrie', 301);
Route::redirect('/secteurs/agroalimentaire', '/secteurs/agro-industrie', 301);
Route::redirect('/agro-industrie', '/secteurs/agro-industrie', 301);

// Site public
Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/secteurs', 'pages::sectors.index')->name('sectors.index');
Route::livewire('/secteurs/{sector:slug}', 'pages::sectors.show')->name('sectors.show');
Route::livewire('/expertises', 'pages::expertises.index')->name('expertises.index');
Route::livewire('/realisations', 'pages::projects.index')->name('projects.index');
Route::livewire('/realisations/{project:slug}', 'pages::projects.show')->name('projects.show');
Route::livewire('/contact', 'pages::contact')->name('contact');
Route::livewire('/mentions-legales', 'pages::page')->name('legal')->defaults('page_slug', 'mentions-legales');
Route::livewire('/politique-de-confidentialite', 'pages::page')->name('privacy')->defaults('page_slug', 'politique-de-confidentialite');
Route::livewire('/pages/le-groupe', 'pages::groupe')->name('pages.groupe');
Route::livewire('/pages/{page:slug}', 'pages::page')->name('pages.show')->where('page', '^(?!mentions-legales$|politique-de-confidentialite$).*$');

// Sitemap
Route::get('/robots.txt', fn () => response(
    "User-agent: *\nAllow: /\nDisallow: /admin\n\nSitemap: ".url('/sitemap.xml')."\n",
    200,
    ['Content-Type' => 'text/plain'],
))->name('robots');

Route::get('/sitemap.xml', function () {
    $xml = Cache::remember('sitemap.xml.v1', 3600, function (): string {
        $sitemap = Sitemap::create()
            ->add(Url::create('/')->setPriority(1.0)->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY))
            ->add(Url::create('/secteurs')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create('/expertises')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create('/realisations')->setPriority(0.9)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
            ->add(Url::create('/contact')->setPriority(0.7)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY))
            ->add(Url::create('/mentions-legales')->setPriority(0.3)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY))
            ->add(Url::create('/politique-de-confidentialite')->setPriority(0.3)->setChangeFrequency(Url::CHANGE_FREQUENCY_YEARLY));

        Project::published()->latest()->each(
            fn (Project $project) => $sitemap->add(Url::create("/realisations/{$project->slug}")->setPriority(0.7)->setLastModificationDate($project->updated_at)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
        );

        Page::published()
            ->whereNotIn('slug', ['mentions-legales', 'politique-de-confidentialite'])
            ->get()
            ->each(fn (Page $page) => $sitemap->add(Url::create("/pages/{$page->slug}")->setPriority(0.5)->setLastModificationDate($page->updated_at)->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)));

        Sector::active()->ordered()->each(
            fn (Sector $sector) => $sitemap->add(Url::create("/secteurs/{$sector->slug}")->setPriority(0.8)->setLastModificationDate($sector->updated_at)->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY))
        );

        return $sitemap->render();
    });

    return response($xml, 200, ['Content-Type' => 'text/xml; charset=UTF-8']);
})->name('sitemap');

// Invitation : définition du mot de passe (lien signé, 7 jours)
Route::livewire('/invitation/{user}', 'pages::invitation')->middleware('signed')->name('invitation.accept');

// Administration (V1)
Route::middleware(['auth', 'password.changed'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('/secteurs', 'pages::admin.sectors.index')->name('admin.sectors');
    Route::redirect('/contenus-secteurs', '/admin/secteurs');
    Route::livewire('/expertises', 'pages::admin.expertises.index')->name('admin.expertises');
    Route::livewire('/services', 'pages::admin.services.index')->name('admin.services');
    Route::livewire('/realisations', 'pages::admin.projects.index')->name('admin.projects');
    Route::livewire('/temoignages', 'pages::admin.testimonials.index')->name('admin.testimonials');
    Route::livewire('/statistiques', 'pages::admin.statistics.index')->name('admin.statistics');
    Route::livewire('/pages', 'pages::admin.pages.index')->name('admin.pages');
    Route::livewire('/parametres', 'pages::admin.settings.index')->name('admin.settings');
    Route::livewire('/prospects', 'pages::admin.leads.index')->name('admin.leads');
    Route::livewire('/utilisateurs', 'pages::admin.users.index')->name('admin.users');
    Route::livewire('/roles', 'pages::admin.roles.index')->name('admin.roles');
});

require __DIR__.'/settings.php';
