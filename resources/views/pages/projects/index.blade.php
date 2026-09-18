<?php

use App\Models\Project;
use App\Models\Sector;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::public')] class extends Component
{
    use WithPagination;

    #[Url]
    public ?int $sector_id = null;

    #[Computed]
    public function activeSector(): ?Sector
    {
        if ($this->sector_id === null) {
            return null;
        }

        return Sector::active()->whereKey($this->sector_id)->first();
    }

    #[Computed]
    public function projects()
    {
        return Project::published()
            ->with('sectors')
            ->when($this->activeSector, fn ($query) => $query->whereHas('sectors', fn ($sectors) => $sectors->whereKey($this->activeSector->id)))
            ->latest()
            ->paginate(9);
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        <img src="{{ asset('images/heroes/realisations.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-12">
            <div class="text-cuivre font-display font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-display font-extrabold text-4xl mb-6">Réalisations</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">Découvrez nos projets livrés et en cours.</p>

        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
        @if($this->activeSector)
            <div class="mb-8 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-bordure bg-surface px-5 py-4">
                <p class="font-display text-sm font-semibold text-nuit">
                    Réalisations du secteur <span class="text-cuivre">{{ $this->activeSector->name }}</span>
                </p>
                <a href="{{ route('projects.index') }}" class="inline-flex items-center gap-2 font-display text-sm font-semibold text-cuivre transition-colors hover:text-nuit focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre">
                    Voir toutes les réalisations
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        @endif

        <p class="mb-10 text-sm text-ardoise" aria-live="polite">
            {{ $this->projects->total() }} réalisation{{ $this->projects->total() > 1 ? 's' : '' }}
        </p>

        @if($this->projects->isEmpty())
            <div class="rounded-lg border border-bordure bg-surface p-12 text-center">
                <span class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-cuivre/10" aria-hidden="true">
                    <svg class="h-7 w-7 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
                <div class="font-display font-extrabold text-xl mb-2">Aucune réalisation publiée</div>
                <p class="text-ardoise">Nos réalisations seront publiées prochainement.</p>
            </div>
        @else
            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 transition-opacity" wire:loading.class="opacity-40">
                @foreach($this->projects as $project)
                    @php($position = str_pad((string) ($this->projects->firstItem() + $loop->index), 2, '0', STR_PAD_LEFT))
                    <a wire:key="project-{{ $project->id }}" href="{{ route('projects.show', $project->slug) }}" class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md">
                        <div class="relative h-56 overflow-hidden bg-nuit lg:h-64">
                            @if($project->getFirstMediaUrl('cover', 'thumb'))
                                <img src="{{ $project->getFirstMediaUrl('cover', 'thumb') }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                            @else
                                <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;" aria-hidden="true"></div>
                                <span class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5" aria-hidden="true">{{ mb_substr($project->title, 0, 1) }}</span>
                            @endif
                            <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10" aria-hidden="true"></div>
                            <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                                @if($project->status)
                                    <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">{{ $project->status->label() }}</span>
                                @else
                                    <span aria-hidden="true"></span>
                                @endif
                                <span class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-6 lg:p-8">
                            @if($project->sectors->isNotEmpty())
                                <div class="mb-3 flex flex-wrap gap-2">
                                    @foreach($project->sectors as $sector)
                                        <span wire:key="project-{{ $project->id }}-sector-{{ $sector->id }}" class="rounded-full bg-cuivre/10 px-3 py-1 text-[11px] font-display font-semibold uppercase tracking-wider text-cuivre">{{ $sector->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                            <h2 class="font-display text-2xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">{{ $project->title }}</h2>
                            @if($project->short_description)
                                <p class="mt-3 text-sm text-ardoise leading-relaxed line-clamp-2">{{ $project->short_description }}</p>
                            @endif
                            @if(! empty($project->results))
                                <ul class="mt-4 space-y-1.5 text-sm text-ardoise">
                                    @foreach(array_slice($project->results, 0, 3) as $result)
                                        <li wire:key="project-{{ $project->id }}-result-{{ $loop->index }}" class="flex gap-2">
                                            <span class="text-cuivre font-bold" aria-hidden="true">✓</span>
                                            <span class="line-clamp-1">{{ $result }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="mt-auto flex flex-wrap items-center gap-3 pt-6">
                                @php($meta = collect([$project->location, $project->project_date?->format('Y')])->filter())
                                @if($meta->isNotEmpty())
                                    <span class="text-xs text-ardoise">{{ $meta->implode(' · ') }}</span>
                                @endif
                                <span class="ml-auto inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 group-hover:bg-cuivre group-hover:text-nuit">
                                    Voir
                                    <span class="inline-block transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($this->projects->hasPages())
                <div class="mt-12 flex justify-center">
                    <flux:pagination :paginator="$this->projects" />
                </div>
            @endif
        @endif
    </section>
</div>
