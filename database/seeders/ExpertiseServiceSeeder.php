<?php

namespace Database\Seeders;

use App\Models\Expertise;
use App\Models\Sector;
use App\Models\Service;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpertiseServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectorIds = Sector::query()->pluck('id', 'slug');

        $expertises = [
            [
                'name' => 'Études & ingénierie',
                'sectors' => ['btp'],
                'short_description' => 'Études techniques, dimensionnement et pilotage de vos projets.',
                'description' => 'Nos bureaux d’études accompagnent les maîtres d’ouvrage publics et privés de la faisabilité à l’exécution : relevés, études de sol, dimensionnement, notes de calcul, plans d’exécution et suivi technique. Chaque décision est sécurisée par une analyse rigoureuse des coûts, des délais et des contraintes réglementaires.',
                'benefits' => [
                    'Dossiers techniques conformes et complets',
                    'Optimisation des coûts dès la conception',
                    'Réduction des aléas de chantier',
                    'Interlocuteur unique de l’esquisse à la réception',
                ],
                'process_steps' => [
                    'Diagnostic du besoin et visite du site',
                    'Études préliminaires et avant-projet',
                    'Dimensionnement et plans d’exécution',
                    'Suivi technique et réception des travaux',
                ],
            ],
            [
                'name' => 'Construction & réalisation',
                'sectors' => ['btp'],
                'short_description' => 'Exécution de bâtiments, ouvrages d’art et infrastructures.',
                'description' => 'Du terrassement à la livraison, nos équipes conduisent vos chantiers avec des moyens matériels adaptés et un pilotage qualité-sécurité rigoureux. Gros œuvre, VRD, ouvrages d’art et bâtiments industriels ou résidentiels : nous tenons les délais et garantissons des ouvrages durables.',
                'benefits' => [
                    'Respect des délais contractuels',
                    'Plan qualité et sécurité appliqué sur site',
                    'Encadrement expérimenté par des conducteurs de travaux',
                    'Maîtrise des coûts par lot',
                ],
                'process_steps' => [
                    'Installation de chantier et sécurisation',
                    'Terrassement et fondations',
                    'Gros œuvre et corps d’état',
                    'Essais, levée des réserves et livraison',
                ],
            ],
            [
                'name' => 'Développement immobilier',
                'sectors' => ['immobilier'],
                'short_description' => 'Programmes résidentiels et immobiliers d’entreprise clés en main.',
                'description' => 'Nous identifions et valorisons le foncier pour concevoir des programmes adaptés aux usages : résidences, bureaux, plateformes logistiques et locaux commerciaux. Notre maîtrise de la chaîne complète — de l’acquisition à la gestion locative — sécurise votre investissement et vos revenus.',
                'benefits' => [
                    'Sécurisation foncière et juridique',
                    'Emplacements stratégiques à Abidjan et en région',
                    'Finitions soignées et normes respectées',
                    'Accompagnement locatif après livraison',
                ],
                'process_steps' => [
                    'Sélection et audit du foncier',
                    'Étude de marché et définition du programme',
                    'Construction et commercialisation',
                    'Livraison et gestion patrimoniale',
                ],
            ],
            [
                'name' => 'Solutions énergétiques',
                'sectors' => ['energie'],
                'short_description' => 'Production solaire, systèmes hybrides et réseaux électriques.',
                'description' => 'Nous concevons, installons et maintenons des solutions énergétiques fiables pour les industries, les opérateurs télécoms et les collectivités : centrales solaires photovoltaïques, stockage par batteries, groupes de secours, lignes HT/BT et postes de transformation. Chaque installation est dimensionnée sur mesure et supervisée dans la durée.',
                'benefits' => [
                    'Réduction de la facture énergétique',
                    'Continuité d’alimentation garantie',
                    'Énergie solaire adaptée aux sites isolés',
                    'Maintenance et supervision continues',
                ],
                'process_steps' => [
                    'Audit énergétique et relevés sur site',
                    'Dimensionnement et étude d’impact',
                    'Installation et raccordement',
                    'Mise en service, suivi et maintenance',
                ],
            ],
            [
                'name' => 'Solutions agro-industrielles',
                'sectors' => ['agro-industrie'],
                'short_description' => 'Transformation et conditionnement des productions locales.',
                'description' => 'Nos unités transforment et conditionnent les matières premières agricoles — huilerie, rizerie, conserverie — avec des procédés modernes et une traçabilité complète. De la collecte auprès des coopératives à l’expédition des produits finis, nous garantissons qualité, sécurité alimentaire et valeur ajoutée locale.',
                'benefits' => [
                    'Traçabilité de la récolte au produit fini',
                    'Respect des normes de sécurité alimentaire',
                    'Réduction des pertes post-récolte',
                    'Approvisionnement sécurisé des coopératives',
                ],
                'process_steps' => [
                    'Collecte et contrôle des matières premières',
                    'Transformation et conditionnement',
                    'Contrôle qualité par lot',
                    'Stockage et distribution',
                ],
            ],
        ];

        foreach ($expertises as $index => $data) {
            $expertise = Expertise::updateOrCreate(
                ['slug' => Str::slug($data['name'])],
                [
                    'name' => $data['name'],
                    'short_description' => $data['short_description'],
                    'description' => $data['description'],
                    'benefits' => $data['benefits'],
                    'process_steps' => $data['process_steps'],
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            $expertise->sectors()->syncWithoutDetaching(
                $sectorIds->only($data['sectors'])->values()->all()
            );

            $service = Service::updateOrCreate(
                ['slug' => Str::slug($data['name'].' accompagnement')],
                [
                    'name' => $data['name'].' — accompagnement',
                    'short_description' => 'Prestation associée à '.$data['name'].'.',
                    'description' => 'Nos équipes interviennent sur toute la chaîne : cadrage du besoin, exécution, contrôle qualité et suivi après livraison, avec un interlocuteur unique et un reporting régulier.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );

            $service->expertises()->syncWithoutDetaching([$expertise->id]);
        }
    }
}
