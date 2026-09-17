<?php

namespace Database\Seeders;

use App\Models\Sector;
use Illuminate\Database\Seeder;

class SectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = [
            [
                'name' => 'BTP',
                'slug' => 'btp',
                'short_description' => 'Bâtiments, infrastructures et travaux publics.',
                'hero_title' => 'Construire des ouvrages qui durent.',
                'hero_description' => 'Bâtiments, infrastructures et travaux publics réalisés avec exigence, dans le respect des délais et des normes.',
                'hero_cta_label' => 'Découvrir le BTP',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Immobilier',
                'slug' => 'immobilier',
                'short_description' => 'Développement et valorisation immobilière.',
                'hero_title' => 'Valoriser le foncier, livrer des lieux de vie.',
                'hero_description' => 'Développement et réalisation de programmes immobiliers créateurs de valeur, à Abidjan et en région.',
                'hero_cta_label' => 'Découvrir l’immobilier',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Énergie',
                'slug' => 'energie',
                'short_description' => 'Solutions énergétiques fiables et durables.',
                'hero_title' => 'L’énergie au service du développement.',
                'hero_description' => 'Des solutions énergétiques fiables pour alimenter durablement entreprises et collectivités.',
                'hero_cta_label' => 'Découvrir l’énergie',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => 'Agro-industrie',
                'slug' => 'agro-industrie',
                'short_description' => 'Transformation industrielle des matières premières agricoles.',
                'hero_title' => 'Transformer local, créer de la valeur.',
                'hero_description' => 'Unités de transformation agro-industrielle — huilerie, rizerie, conserverie, conditionnement — de la matière première au produit fini.',
                'hero_cta_label' => 'Découvrir l’agro-industrie',
                'is_active' => true,
                'sort_order' => 4,
            ],
        ];

        foreach ($sectors as $index => $data) {
            Sector::updateOrCreate(
                ['slug' => $data['slug']],
                $data + ['is_locked' => true, 'hero_is_active' => true, 'hero_sort_order' => $index + 1]
            );
        }
    }
}
