<?php

use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

// Site public
Route::redirect('/secteurs/agroalimentaire', '/secteurs/agro-industrie', 301);
Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/secteurs', 'pages::sectors.index')->name('sectors.index');
Route::livewire('/secteurs/{sector:slug}', 'pages::sectors.show')->name('sectors.show');
Route::livewire('/expertises', 'pages::expertises.index')->name('expertises.index');
Route::livewire('/expertises/{expertise:slug}', 'pages::expertises.show')->name('expertises.show');
Route::livewire('/realisations', 'pages::projects.index')->name('projects.index');
Route::livewire('/realisations/{project:slug}', 'pages::projects.show')->name('projects.show');
Route::livewire('/contact', 'pages::contact')->name('contact');
Route::livewire('/mentions-legales', 'pages::legal')->name('legal');
Route::livewire('/politique-de-confidentialite', 'pages::privacy')->name('privacy');

// Redirects 301 (compat SEO)
Route::redirect('/btp', '/secteurs/btp', 301);
Route::redirect('/immobilier', '/secteurs/immobilier', 301);
Route::redirect('/energie', '/secteurs/energie', 301);
Route::redirect('/agroalimentaire', '/secteurs/agro-industrie', 301);
Route::redirect('/agro-industrie', '/secteurs/agro-industrie', 301);

// Sitemap
Route::get('/sitemap.xml', function () {
    $sitemap = Sitemap::create()
        ->add(Url::create('/')->setPriority(1.0))
        ->add(Url::create('/secteurs')->setPriority(0.9))
        ->add(Url::create('/expertises')->setPriority(0.9))
        ->add(Url::create('/realisations')->setPriority(0.9))
        ->add(Url::create('/contact')->setPriority(0.7))
        ->add(Url::create('/mentions-legales')->setPriority(0.3))
        ->add(Url::create('/politique-de-confidentialite')->setPriority(0.3));

    Sector::active()->ordered()->each(
        fn (Sector $sector) => $sitemap->add(Url::create("/secteurs/{$sector->slug}")->setPriority(0.8))
    );
    Expertise::active()->ordered()->each(
        fn (Expertise $expertise) => $sitemap->add(Url::create("/expertises/{$expertise->slug}")->setPriority(0.8))
    );
    Project::published()->latest()->each(
        fn (Project $project) => $sitemap->add(Url::create("/realisations/{$project->slug}")->setPriority(0.7))
    );

    return $sitemap->toResponse(request());
})->name('sitemap');

// Administration (V1)
Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('admin.dashboard');
    Route::livewire('/secteurs', 'pages::admin.sectors.index')->name('admin.sectors');
    Route::livewire('/expertises', 'pages::admin.expertises.index')->name('admin.expertises');
    Route::livewire('/services', 'pages::admin.services.index')->name('admin.services');
    Route::livewire('/realisations', 'pages::admin.projects.index')->name('admin.projects');
    Route::livewire('/prospects', 'pages::admin.leads.index')->name('admin.leads');
    Route::livewire('/utilisateurs', 'pages::admin.users.index')->name('admin.users');
    Route::livewire('/roles', 'pages::admin.roles.index')->name('admin.roles');
});

require __DIR__.'/settings.php';
