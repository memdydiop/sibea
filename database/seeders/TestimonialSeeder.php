<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Contenu de DÉMONSTRATION — à remplacer par de vrais avis clients.
 *
 * Volontairement absent de DatabaseSeeder : à lancer à la main en dev
 * (php artisan db:seed --class=TestimonialSeeder). Ne jamais publier
 * ces avis fictifs en production.
 */
class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testimonials = [
            [
                'author_name' => 'Kouassi M.',
                'position' => 'Promoteur',
                'organization' => 'Abidjan',
                'content' => 'Équipe sérieuse, chantier livré dans les délais avec une qualité au rendez-vous. Je recommande.',
                'sort_order' => 1,
            ],
            [
                'author_name' => 'Fatou D.',
                'position' => 'Particulier',
                'organization' => 'Cocody',
                'content' => 'Communication claire du début à la fin et un résultat conforme au devis. Très professionnel.',
                'sort_order' => 2,
            ],
            [
                'author_name' => 'Yao K.',
                'position' => 'Gérant',
                'organization' => 'PME locale',
                'content' => 'Un accompagnement rigoureux, de l’étude à la réception. Nos locaux ont été transformés.',
                'sort_order' => 3,
            ],
        ];

        foreach ($testimonials as $data) {
            Testimonial::updateOrCreate(
                ['author_name' => $data['author_name']],
                $data + ['is_active' => true]
            );
        }
    }
}
