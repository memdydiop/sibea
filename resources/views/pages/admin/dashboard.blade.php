<?php

use App\Models\Expertise;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Sector;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::app')] #[Title('Administration')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('view_dashboard'), 403);
    }

    #[Computed]
    public function stats(): array
    {
        return [
            'secteurs' => Sector::active()->count(),
            'expertises' => Expertise::active()->count(),
            'projets' => Project::published()->count(),
            'prospects' => Lead::count(),
        ];
    }
};
?>

<div class="p-6">
    <flux:heading size="xl" class="mb-2">Tableau de bord</flux:heading>
    <flux:subheading class="mb-8">Vue d’ensemble du site vitrine SIBEA.</flux:subheading>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <flux:card>
            <div class="font-[Manrope] font-extrabold text-3xl">{{ $this->stats['secteurs'] }}</div>
            <div class="text-sm text-zinc-500">Secteurs actifs</div>
        </flux:card>
        <flux:card>
            <div class="font-[Manrope] font-extrabold text-3xl">{{ $this->stats['expertises'] }}</div>
            <div class="text-sm text-zinc-500">Expertises actives</div>
        </flux:card>
        <flux:card>
            <div class="font-[Manrope] font-extrabold text-3xl">{{ $this->stats['projets'] }}</div>
            <div class="text-sm text-zinc-500">Projets publiés</div>
        </flux:card>
        <flux:card>
            <div class="font-[Manrope] font-extrabold text-3xl">{{ $this->stats['prospects'] }}</div>
            <div class="text-sm text-zinc-500">Prospects</div>
        </flux:card>
    </div>
</div>
