<?php

use App\Support\SitemapCache;
use Illuminate\Support\Facades\Route;

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
    $xml = SitemapCache::remember();

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
    Route::livewire('/roles/{role}', 'pages::admin.roles.show')->name('admin.roles.show');
    Route::redirect('/roles', '/admin/utilisateurs');
});

require __DIR__.'/settings.php';
