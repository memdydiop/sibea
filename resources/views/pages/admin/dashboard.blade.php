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

    /**
     * @return array<string, int>
     */
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

    #[Computed]
    public function recentLeads()
    {
        return auth()->user()->can('manage_leads')
            ? Lead::latest()->take(5)->get()
            : collect();
    }

    #[Computed]
    public function recentProjects()
    {
        return auth()->user()->can('view_projects')
            ? Project::with('sectors')->latest()->take(5)->get()
            : collect();
    }

    #[Computed]
    public function draftProjects(): int
    {
        return auth()->user()->can('view_projects')
            ? Project::where('is_published', false)->count()
            : 0;
    }
};
?>

<div class="p-6">
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Tableau de bord</flux:heading>
            <flux:subheading>Vue d’ensemble du site vitrine SIBEA.</flux:subheading>
        </div>
        <div class="flex flex-wrap gap-2">
            @can('manage_projects')
                <flux:button href="{{ route('admin.projects') }}" wire:navigate icon="photo">Réalisations</flux:button>
            @endcan
            @can('manage_leads')
                <flux:button href="{{ route('admin.leads') }}" wire:navigate icon="users">Prospects</flux:button>
            @endcan
            <flux:button href="{{ route('home') }}" target="_blank" variant="ghost" icon="globe-alt">Voir le site</flux:button>
        </div>
    </div>

    <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card>
            <div class="font-display text-3xl font-extrabold">{{ $this->stats['secteurs'] }}</div>
            <div class="text-sm text-zinc-500">Secteurs actifs</div>
        </flux:card>
        <flux:card>
            <div class="font-display text-3xl font-extrabold">{{ $this->stats['expertises'] }}</div>
            <div class="text-sm text-zinc-500">Expertises actives</div>
        </flux:card>
        <flux:card>
            <div class="font-display text-3xl font-extrabold">{{ $this->stats['projets'] }}</div>
            <div class="text-sm text-zinc-500">Projets publiés</div>
        </flux:card>
        <flux:card>
            <div class="font-display text-3xl font-extrabold">{{ $this->stats['prospects'] }}</div>
            <div class="text-sm text-zinc-500">Prospects</div>
        </flux:card>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        @if(auth()->user()->can('manage_leads'))
            <flux:card>
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">Derniers prospects</flux:heading>
                    <flux:link :href="route('admin.leads')" wire:navigate class="text-sm">Tout voir</flux:link>
                </div>

                @if($this->recentLeads->isEmpty())
                    <flux:text class="text-zinc-500">Aucun prospect pour le moment.</flux:text>
                @else
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->recentLeads as $lead)
                            <li wire:key="dashboard-lead-{{ $lead->id }}" class="flex items-center gap-3 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium">{{ $lead->name }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $lead->company ?: $lead->email }}</div>
                                </div>
                                <flux:badge size="sm" color="zinc">{{ $lead->status->label() }}</flux:badge>
                                <span class="whitespace-nowrap text-xs text-zinc-500">{{ $lead->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </flux:card>
        @endif

        @if(auth()->user()->can('view_projects'))
            <flux:card>
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">Réalisations récentes</flux:heading>
                    <flux:link :href="route('admin.projects')" wire:navigate class="text-sm">Tout voir</flux:link>
                </div>

                @if($this->recentProjects->isEmpty())
                    <flux:text class="text-zinc-500">Aucune réalisation enregistrée.</flux:text>
                @else
                    <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($this->recentProjects as $project)
                            <li wire:key="dashboard-project-{{ $project->id }}" class="flex items-center gap-3 py-2.5">
                                <div class="min-w-0 flex-1">
                                    <div class="truncate text-sm font-medium">{{ $project->title }}</div>
                                    <div class="truncate text-xs text-zinc-500">{{ $project->sectors->pluck('name')->join(', ') ?: '—' }}</div>
                                </div>
                                @if($project->is_published)
                                    <flux:badge size="sm" color="green">Publié</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Brouillon</flux:badge>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if($this->draftProjects > 0)
                    <flux:text class="mt-4 text-xs text-zinc-500">{{ $this->draftProjects }} brouillon(s) en attente de publication.</flux:text>
                @endif
            </flux:card>
        @endif
    </div>
</div>
