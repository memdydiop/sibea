<?php

use App\Models\Project;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

// Site public
Route::livewire('/', 'pages::home')->name('home');
Route::livewire('/secteurs', 'pages::sectors.index')->name('sectors.index');
Route::livewire('/expertises', 'pages::expertises.index')->name('expertises.index');
Route::livewire('/realisations', 'pages::projects.index')->name('projects.index');
Route::livewire('/realisations/{project:slug}', 'pages::projects.show')->name('projects.show');
Route::livewire('/contact', 'pages::contact')->name('contact');
Route::livewire('/mentions-legales', 'pages::legal')->name('legal');
Route::livewire('/politique-de-confidentialite', 'pages::privacy')->name('privacy');

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
