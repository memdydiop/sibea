<?php

namespace App\Console\Commands;

use App\Models\Lead;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('leads:purge')]
#[Description('Purge les prospects vieux de plus de 3 ans (RGPD)')]
class PurgeLeads extends Command
{
    public function handle(): int
    {
        $cutoff = now()->subYears(3);

        $deleted = 0;
        Lead::where('created_at', '<', $cutoff)->chunkById(200, function ($leads) use (&$deleted): void {
            $deleted += $leads->count();
            Lead::whereIn('id', $leads->pluck('id'))->delete();
        });

        $this->info("{$deleted} prospect(s) purgé(s).");

        return self::SUCCESS;
    }
}
