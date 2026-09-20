<?php

use App\Enums\LeadStatus;
use App\Models\Expertise;
use App\Models\Lead;
use App\Models\Page;
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

    #[Computed]
    public function draftPages(): int
    {
        return auth()->user()->can('manage_pages')
            ? Page::where('is_published', false)->count()
            : 0;
    }

    #[Computed]
    public function newLeads(): int
    {
        return auth()->user()->can('manage_leads')
            ? Lead::where('status', LeadStatus::Nouveau)->count()
            : 0;
    }

    /**
     * @return array<int, array{type: string, title: string, date: mixed, url: string}>
     */
    #[Computed]
    public function recentActivity(): array
    {
        $items = collect();

        if (auth()->user()->can('view_projects')) {
            foreach (Project::latest('updated_at')->take(3)->get() as $project) {
                $items->push([
                    'type' => 'Projet',
                    'title' => $project->title,
                    'date' => $project->updated_at,
                    'url' => route('admin.projects'),
                ]);
            }
        }

        if (auth()->user()->can('manage_pages')) {
            foreach (Page::latest('updated_at')->take(2)->get() as $page) {
                $items->push([
                    'type' => 'Page',
                    'title' => $page->title,
                    'date' => $page->updated_at,
                    'url' => route('admin.pages'),
                ]);
            }
        }

        if (auth()->user()->can('manage_sectors')) {
            foreach (Sector::latest('updated_at')->take(2)->get() as $sector) {
                $items->push([
                    'type' => 'Secteur',
                    'title' => $sector->name,
                    'date' => $sector->updated_at,
                    'url' => route('admin.sectors'),
                ]);
            }
        }

        if (auth()->user()->can('manage_expertises')) {
            foreach (Expertise::latest('updated_at')->take(2)->get() as $expertise) {
                $items->push([
                    'type' => 'Expertise',
                    'title' => $expertise->name,
                    'date' => $expertise->updated_at,
                    'url' => route('admin.expertises'),
                ]);
            }
        }

        return $items->sortByDesc('date')->take(8)->values()->all();
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
        @can('manage_sectors')
            <a href="{{ route('admin.sectors') }}" wire:navigate class="block rounded-lg transition hover:-translate-y-0.5">
        @endcan
        <x-admin.card>
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600 dark:bg-blue-400/10 dark:text-blue-400">
                    <flux:icon.building-office-2 class="size-5" />
                </div>
                <div>
                    <div class="font-display text-2xl font-extrabold">{{ $this->stats['secteurs'] }}</div>
                    <div class="text-sm text-zinc-500">Secteurs actifs</div>
                </div>
            </div>
        </x-admin.card>
        @can('manage_sectors')
            </a>
        @endcan
        @can('manage_expertises')
            <a href="{{ route('admin.expertises') }}" wire:navigate class="block rounded-lg transition hover:-translate-y-0.5">
        @endcan
        <x-admin.card>
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-violet-500/10 text-violet-600 dark:bg-violet-400/10 dark:text-violet-400">
                    <flux:icon.academic-cap class="size-5" />
                </div>
                <div>
                    <div class="font-display text-2xl font-extrabold">{{ $this->stats['expertises'] }}</div>
                    <div class="text-sm text-zinc-500">Expertises actives</div>
                </div>
            </div>
        </x-admin.card>
        @can('manage_expertises')
            </a>
        @endcan
        @can('view_projects')
            <a href="{{ route('admin.projects') }}" wire:navigate class="block rounded-lg transition hover:-translate-y-0.5">
        @endcan
        <x-admin.card>
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-green-500/10 text-green-600 dark:bg-green-400/10 dark:text-green-400">
                    <flux:icon.photo class="size-5" />
                </div>
                <div>
                    <div class="font-display text-2xl font-extrabold">{{ $this->stats['projets'] }}</div>
                    <div class="text-sm text-zinc-500">Projets publiés</div>
                </div>
            </div>
        </x-admin.card>
        @can('view_projects')
            </a>
        @endcan
        @can('manage_leads')
            <a href="{{ route('admin.leads') }}" wire:navigate class="block rounded-lg transition hover:-translate-y-0.5">
        @endcan
        <x-admin.card>
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:bg-amber-400/10 dark:text-amber-400">
                    <flux:icon.users class="size-5" />
                </div>
                <div>
                    <div class="font-display text-2xl font-extrabold">{{ $this->stats['prospects'] }}</div>
                    <div class="text-sm text-zinc-500">Prospects</div>
                </div>
            </div>
        </x-admin.card>
        @can('manage_leads')
            </a>
        @endcan
    </div>

    @if($this->newLeads > 0 || $this->draftProjects > 0 || $this->draftPages > 0)
        <x-admin.card title="À traiter" class="mb-8">
            <ul class="space-y-3">
                @if($this->newLeads > 0)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-sm"><strong>{{ $this->newLeads }}</strong> prospect(s) nouveau(x) à qualifier</span>
                        <flux:button size="sm" variant="ghost" href="{{ route('admin.leads') }}" wire:navigate>Voir</flux:button>
                    </li>
                @endif
                @if($this->draftProjects > 0)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-sm"><strong>{{ $this->draftProjects }}</strong> réalisation(s) en brouillon</span>
                        <flux:button size="sm" variant="ghost" href="{{ route('admin.projects') }}" wire:navigate>Voir</flux:button>
                    </li>
                @endif
                @if($this->draftPages > 0)
                    <li class="flex items-center justify-between gap-3">
                        <span class="text-sm"><strong>{{ $this->draftPages }}</strong> page(s) en brouillon</span>
                        <flux:button size="sm" variant="ghost" href="{{ route('admin.pages') }}" wire:navigate>Voir</flux:button>
                    </li>
                @endif
            </ul>
        </x-admin.card>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
        @if(auth()->user()->can('manage_leads'))
            <x-admin.card title="Derniers prospects">
                <x-slot:actions>
                    <flux:link :href="route('admin.leads')" wire:navigate class="text-sm">Tout voir</flux:link>
                </x-slot:actions>

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
                                <flux:badge size="sm" color="zinc">{{ $lead->status?->label() ?? '—' }}</flux:badge>
                                <span class="whitespace-nowrap text-xs text-zinc-500">{{ $lead->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.card>
        @endif

        @if(auth()->user()->can('view_projects'))
            <x-admin.card title="Réalisations récentes">
                <x-slot:actions>
                    <flux:link :href="route('admin.projects')" wire:navigate class="text-sm">Tout voir</flux:link>
                </x-slot:actions>

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
            </x-admin.card>
        @endif

        @if(count($this->recentActivity) > 0)
            <x-admin.card title="Activité récente">
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->recentActivity as $activity)
                        <li wire:key="dashboard-activity-{{ $activity['type'] }}-{{ $loop->index }}" class="flex items-center gap-3 py-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">{{ $activity['title'] }}</div>
                                <div class="text-xs text-zinc-500">{{ $activity['type'] }} · {{ $activity['date']?->diffForHumans() }}</div>
                            </div>
                            <flux:button size="xs" variant="ghost" icon="arrow-right" :href="$activity['url']" wire:navigate aria-label="Voir" />
                        </li>
                    @endforeach
                </ul>
            </x-admin.card>
        @endif
    </div>
</div>
