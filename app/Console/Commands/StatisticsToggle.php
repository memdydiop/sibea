<?php

namespace App\Console\Commands;

use App\Models\Statistic;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('statistics:toggle {key : Clé de la statistique}')]
#[Description('Active ou désactive un chiffre clé (V1 : lecture seule, pas d’écran admin)')]
class StatisticsToggle extends Command
{
    public function handle(): int
    {
        $statistic = Statistic::where('key', $this->argument('key'))->first();

        if ($statistic === null) {
            $this->error("Statistique [{$this->argument('key')}] introuvable.");

            return self::FAILURE;
        }

        $statistic->update(['is_active' => ! $statistic->is_active]);

        $this->info("Statistique [{$statistic->key}] : ".($statistic->is_active ? 'activée' : 'désactivée').'.');

        return self::SUCCESS;
    }
}
