<?php

namespace Database\Seeders;

use App\Enums\ProjectStatus;
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
                'description' => "Un programme résidentiel de standing pensé pour un foncier urbain contraint, avec parking souterrain et espaces verts partagés.\n\nLe chantier a été conduit en site occupé, sans interruption de voirie, avec un suivi hebdomadaire des riverains.",
                'challenge' => 'Le terrain de 4 200 m² se situe en zone dense, avec un accès unique et des contraintes de voisinage fortes. Le promoteur souhaitait livrer les premiers logements en moins de deux ans.',
                'solution' => "Nous avons opté pour une structure poteaux-poutres coulée en place, une préfabrication partielle des façades et un phasage en deux tranches. La logistique a été planifiée de nuit pour limiter l'impact sur la circulation.",
                'impact' => "24 logements livrés avec deux mois d'avance, 30 emplois directs créés pendant le chantier et une démarche de tri des déchets qui a permis de valoriser près de 70 % des gravats.",
                'location' => 'Abidjan, Cocody',
                'project_date' => '2024-06-01',
                'duration' => '18 mois',
                'surface' => '4 200 m²',
                'budget' => '2,4 milliards FCFA',
                'status' => ProjectStatus::Livre,
                'client_name' => 'Groupe Ivoirien de Promotion',
                'client_publishable' => true,
                'testimonial_quote' => 'Le phasage proposé par SIBEA nous a permis de commercialiser la première tranche avant la fin du gros œuvre. Un vrai partenaire de projet.',
                'testimonial_author' => 'Directeur de promotion, Groupe Ivoirien de Promotion',
                'latitude' => 5.3540,
                'longitude' => -3.9861,
                'results' => ['24 logements', '18 mois', '70 % de gravats valorisés'],
                'sectors' => ['btp', 'immobilier'],
            ],
            [
                'title' => 'Centrale solaire 500 kWc — Yamoussoukro',
                'short_description' => 'Installation photovoltaïque pour un site industriel.',
                'description' => "Une centrale solaire en autoconsommation raccordée au réseau existant du site, avec supervision temps réel de la production.\n\nL'exploitation a été formée à la maintenance préventive des onduleurs et des chaînes photovoltaïques.",
                'challenge' => "Le site industriel subissait des coupures fréquentes et un coût énergétique en forte hausse. L'objectif était de sécuriser la production sans remplacer l'installation existante.",
                'solution' => "Nous avons dimensionné une centrale de 500 kWc en toiture et ombrières, couplée à un système de stockage tampon et à une supervision connectée. L'intégration au réseau existant s'est faite sans arrêt de production.",
                'impact' => '620 MWh produits par an, soit près de 30 % des besoins du site, et une réduction de 45 % des coupures subies par les lignes de production.',
                'location' => 'Yamoussoukro',
                'project_date' => '2025-02-01',
                'duration' => '6 mois',
                'surface' => '1,2 ha',
                'budget' => '850 millions FCFA',
                'status' => ProjectStatus::Livre,
                'client_name' => 'Industrie agroalimentaire',
                'client_publishable' => false,
                'testimonial_quote' => 'La supervision nous donne enfin de la visibilité sur notre consommation. Les équipes ont été formées et autonomes dès la mise en service.',
                'testimonial_author' => 'Responsable maintenance industrielle',
                'latitude' => 6.8276,
                'longitude' => -5.2893,
                'results' => ['500 kWc', '620 MWh/an', '45 % de coupures en moins'],
                'sectors' => ['energie'],
            ],
            [
                'title' => 'Rizerie moderne — Daloa',
                'short_description' => 'Unité de transformation de riz paddy, 5 t/h.',
                'description' => "Une unité complète de transformation, du séchage au conditionnement, conçue pour les producteurs de la région du Haut-Sassandra.\n\nL'ensemble fonctionne en deux équipes et intègre une station de traitement des effluents.",
                'challenge' => "Les producteurs locaux vendaient leur paddy brut, faute d'infrastructure de transformation à proximité. Le projet devait garantir un approvisionnement régulier et un taux de bris limité.",
                'solution' => 'Nous avons réalisé le bâtiment industriel, installé la ligne de transformation de 5 t/h et raccordé les utilités. Un silo de stockage de 2 000 tonnes a été intégré pour lisser les campagnes.',
                'impact' => '18 emplois permanents créés, 2 000 tonnes de paddy stockées et une valeur ajoutée qui reste désormais dans la région.',
                'location' => 'Daloa',
                'project_date' => '2023-11-01',
                'duration' => '12 mois',
                'surface' => '3 500 m²',
                'budget' => '1,1 milliard FCFA',
                'status' => ProjectStatus::Livre,
                'client_name' => 'Coopérative régionale',
                'client_publishable' => true,
                'testimonial_quote' => 'La rizerie tourne en deux équipes et le taux de bris est inférieur à nos prévisions. Nous envisageons déjà une extension.',
                'testimonial_author' => 'Président de coopérative',
                'latitude' => 6.8776,
                'longitude' => -6.4502,
                'results' => ['5 t/h', '2 000 t de stockage', '18 emplois créés'],
                'sectors' => ['agro-industrie'],
            ],
        ];

        foreach ($projects as $data) {
            $project = Project::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'short_description' => $data['short_description'],
                    'description' => $data['description'],
                    'challenge' => $data['challenge'],
                    'solution' => $data['solution'],
                    'impact' => $data['impact'],
                    'location' => $data['location'],
                    'project_date' => $data['project_date'],
                    'duration' => $data['duration'],
                    'surface' => $data['surface'],
                    'budget' => $data['budget'],
                    'status' => $data['status'],
                    'client_name' => $data['client_name'],
                    'client_publishable' => $data['client_publishable'],
                    'testimonial_quote' => $data['testimonial_quote'],
                    'testimonial_author' => $data['testimonial_author'],
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'results' => $data['results'],
                    'is_active' => true,
                    'is_published' => true,
                ]
            );

            $sectorIds = Sector::whereIn('slug', $data['sectors'])->pluck('id');
            $project->sectors()->sync($sectorIds);
        }
    }
}
