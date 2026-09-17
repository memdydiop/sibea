<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Sector;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Contenu de DÉMONSTRATION — à remplacer par de vraies réalisations.
 *
 * Volontairement absent de DatabaseSeeder : à lancer à la main en dev
 * (php artisan db:seed --class=ProjectSeeder). Ne jamais publier
 * ces projets fictifs en production.
 */
class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $projects = [
            [
                'title' => 'Résidence Les Palmiers — Cocody',
                'short_description' => 'Programme résidentiel de 24 logements livré en 18 mois.',
                'location' => 'Abidjan, Cocody',
                'sectors' => ['btp', 'immobilier'],
            ],
            [
                'title' => 'Centrale solaire 500 kWc — Yamoussoukro',
                'short_description' => 'Installation photovoltaïque pour un site industriel.',
                'location' => 'Yamoussoukro',
                'sectors' => ['energie'],
            ],
            [
                'title' => 'Rizerie moderne — Daloa',
                'short_description' => 'Unité de transformation de riz paddy, 5 t/h.',
                'location' => 'Daloa',
                'sectors' => ['agro-industrie'],
            ],
        ];

        foreach ($projects as $data) {
            $project = Project::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'short_description' => $data['short_description'],
                    'description' => $data['short_description'],
                    'location' => $data['location'],
                    'status' => 'Livré',
                    'is_active' => true,
                    'is_published' => true,
                ]
            );

            $sectorIds = Sector::whereIn('slug', $data['sectors'])->pluck('id');
            $project->sectors()->sync($sectorIds);
        }
    }
}
