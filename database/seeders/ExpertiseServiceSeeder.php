<?php

namespace Database\Seeders;

use App\Models\Expertise;
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
        $expertises = [
            'Études & ingénierie',
            'Construction & réalisation',
            'Développement immobilier',
            'Solutions énergétiques',
            'Solutions agro-industrielles',
        ];

        foreach ($expertises as $index => $name) {
            $expertise = Expertise::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'is_active' => true, 'sort_order' => $index + 1]
            );

            Service::updateOrCreate(
                ['slug' => Str::slug($name.' accompagnement')],
                [
                    'name' => $name.' — accompagnement',
                    'short_description' => 'Prestation associée à '.$name.'.',
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            )->expertises()->syncWithoutDetaching([$expertise->id]);
        }
    }
}
