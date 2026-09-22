<?php

use App\Models\Project;
use App\Support\PageSeo;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Project $project;

    public function mount(): void
    {
        abort_if(! $this->project->is_published, 404);

        $description = Str::squish((string) ($this->project->short_description ?: $this->project->description));

        PageSeo::share($this->project->meta_title ?: $this->project->title, $this->project->meta_description ?: ($description !== '' ? $description : null));
        View::share('ogImage', $this->project->getFirstMediaUrl('cover', 'og') ?: null);

        $this->project->load(['sectors', 'expertises', 'services']);
    }

    #[Computed]
    public function relatedProjects()
    {
        $sectorIds = $this->project->sectors->pluck('id');

        if ($sectorIds->isEmpty()) {
            return collect();
        }

        return Project::published()
            ->whereKeyNot($this->project->getKey())
            ->whereHas('sectors', fn ($query) => $query->whereIn('sectors.id', $sectorIds))
            ->with('sectors')
            ->latest()
            ->take(3)
            ->get();
    }

    #[Computed]
    public function previousProject(): ?Project
    {
        return Project::published()
            ->where('id', '<', $this->project->getKey())
            ->orderByDesc('id')
            ->first();
    }

    #[Computed]
    public function nextProject(): ?Project
    {
        return Project::published()
            ->where('id', '>', $this->project->getKey())
            ->orderBy('id')
            ->first();
    }

    #[Computed]
    public function gallery()
    {
        return $this->project->getMedia('gallery');
    }

    #[Computed]
    public function documents()
    {
        return $this->project->getMedia('documents');
    }
};
?>

@php
    $cover = $project->getFirstMediaUrl('cover', 'medium');

    $facts = collect([
        ['label' => 'Lieu', 'value' => $project->location],
        ['label' => 'Année', 'value' => $project->project_date?->format('Y')],
        ['label' => 'Statut', 'value' => $project->status?->label()],
        ['label' => 'Maître d’ouvrage', 'value' => $project->client_publishable ? $project->client_name : null],
    ])->filter(fn ($fact) => filled($fact['value']));

    $sections = collect([
        ['title' => 'Le projet', 'body' => $project->description],
        ['title' => 'Contexte & enjeu', 'body' => $project->challenge],
        ['title' => 'Notre réponse', 'body' => $project->solution],
        ['title' => 'Impact', 'body' => $project->impact],
    ])->filter(fn ($section) => filled($section['body']))->values();

    $figures = collect($project->results ?? [])->map(function ($result) {
        $result = (string) $result;

        if (preg_match('/^\s*(\d{1,3}(?:[ .]\d{3})*(?:,\d+)?)(?!\d)\s*(.+)$/u', $result, $matches)) {
            return ['value' => trim($matches[1]), 'label' => trim($matches[2])];
        }

        return ['value' => null, 'label' => $result];
    });

    $specs = collect([
        ['label' => 'Durée', 'value' => $project->duration],
        ['label' => 'Surface', 'value' => $project->surface],
        ['label' => 'Budget', 'value' => $project->budget],
    ])->filter(fn ($spec) => filled($spec['value']));

    $hasCoordinates = $project->latitude !== null && $project->longitude !== null;
@endphp

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        @if($cover)
            <img src="{{ $cover }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" decoding="async">
        @endif
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.94) 0%, rgba(11,31,51,0.68) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-14">
            <nav aria-label="Fil d’Ariane" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/60">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-cuivre" wire:navigate>Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('projects.index') }}" class="transition-colors hover:text-cuivre" wire:navigate>Réalisations</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-white/90" aria-current="page">{{ $project->title }}</li>
                </ol>
            </nav>

            <div class="mb-5 flex flex-wrap items-center gap-2">
                @if($project->status)
                    <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">{{ $project->status->label() }}</span>
                @endif
                @foreach($project->sectors as $sector)
                    <a href="{{ route('sectors.show', $sector->slug) }}" class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm transition-colors duration-300 hover:border-cuivre hover:text-cuivre" wire:navigate>{{ $sector->name }}</a>
                @endforeach
            </div>

            <h1 class="font-display font-extrabold text-4xl lg:text-6xl mb-6 max-w-4xl">
                {{ $project->title }}
            </h1>
            @if($project->short_description)
                <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                    {{ $project->short_description }}
                </p>
            @endif
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
        @if($facts->isNotEmpty())
            <div class="mb-12 grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach($facts as $fact)
                    <div wire:key="fact-{{ $loop->index }}" class="rounded-lg border border-bordure bg-surface p-5">
                        <div class="mb-1 font-display text-xs font-semibold uppercase tracking-widest text-cuivre">{{ $fact['label'] }}</div>
                        <div class="font-display font-bold text-nuit">{{ $fact['value'] }}</div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="grid gap-12 lg:grid-cols-3 lg:gap-16">
            <div class="space-y-12 lg:col-span-2">
                @if($sections->isNotEmpty())
                    <div class="space-y-10">
                        @foreach($sections as $section)
                            <div wire:key="section-{{ $loop->index }}">
                                <div class="mb-3 flex items-baseline gap-3">
                                    <span class="font-display text-sm font-extrabold tracking-[0.22em] text-cuivre">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <h2 class="font-display font-extrabold text-nuit text-2xl">{{ $section['title'] }}</h2>
                                </div>
                                <div class="space-y-4 text-ardoise leading-relaxed">
                                    @foreach(preg_split('/\R{2,}/', trim($section['body'])) as $paragraph)
                                        <p>{{ $paragraph }}</p>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($figures->isNotEmpty())
                    <div>
                        <h2 class="font-display font-extrabold text-nuit text-2xl mb-4">Résultats</h2>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($figures as $index => $figure)
                                <div wire:key="figure-{{ $index }}" class="rounded-lg border border-bordure bg-surface p-5">
                                    @if($figure['value'])
                                        <div class="font-display text-4xl font-extrabold leading-none text-cuivre">{{ $figure['value'] }}</div>
                                        <div class="mt-2 text-sm leading-relaxed text-ardoise">{{ $figure['label'] }}</div>
                                    @else
                                        <div class="flex items-start gap-3 text-sm text-anthracite">
                                            <svg class="mt-0.5 size-5 shrink-0 text-cuivre" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ $figure['label'] }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if($project->testimonial_quote)
                    <figure class="relative overflow-hidden rounded-lg border border-bordure bg-surface p-6 lg:p-8">
                        <div class="pointer-events-none absolute -right-2 -top-10 select-none font-display text-[9rem] font-extrabold leading-none text-cuivre/10" aria-hidden="true">“</div>
                        <blockquote class="relative text-lg leading-relaxed text-anthracite">« {{ $project->testimonial_quote }} »</blockquote>
                        @if($project->testimonial_author)
                            <figcaption class="relative mt-5 flex items-center gap-3">
                                <span class="inline-flex size-11 items-center justify-center rounded-full bg-nuit font-display font-bold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($project->testimonial_author, 0, 1)) }}</span>
                                <span class="font-display text-sm font-semibold text-nuit">{{ $project->testimonial_author }}</span>
                            </figcaption>
                        @endif
                    </figure>
                @endif
            </div>

            <aside class="space-y-6">
                @if($specs->isNotEmpty() || $project->expertises->isNotEmpty() || $project->services->isNotEmpty() || $this->documents->isNotEmpty())
                    <div class="rounded-lg border border-bordure bg-surface p-6 lg:p-8">
                        <h2 class="font-display text-lg font-extrabold text-nuit mb-5">Fiche technique</h2>

                        @if($specs->isNotEmpty())
                            <dl class="grid gap-3">
                                @foreach($specs as $spec)
                                    <div wire:key="spec-{{ $loop->index }}" class="flex items-baseline justify-between gap-4 border-b border-bordure pb-3 last:border-0 last:pb-0">
                                        <dt class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">{{ $spec['label'] }}</dt>
                                        <dd class="font-display text-sm font-bold text-nuit">{{ $spec['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        @endif

                        @if($project->expertises->isNotEmpty())
                            <div class="mt-6 border-t border-bordure pt-5">
                                <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Expertises mobilisées</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($project->expertises as $expertise)
                                        <a wire:key="expertise-{{ $expertise->id }}" href="{{ route('expertises.index') }}" class="rounded-full border border-bordure bg-casse px-3 py-1 text-xs font-medium text-anthracite transition-colors hover:border-cuivre hover:text-cuivre" wire:navigate>{{ $expertise->name }}</a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($project->services->isNotEmpty())
                            <div class="mt-6 border-t border-bordure pt-5">
                                <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Prestations</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($project->services as $service)
                                        <span wire:key="service-{{ $service->id }}" class="rounded-full border border-bordure bg-casse px-3 py-1 text-xs font-medium text-anthracite">{{ $service->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($this->documents->isNotEmpty())
                            <div class="mt-6 border-t border-bordure pt-5">
                                <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Documents</div>
                                <ul class="mt-3 space-y-2">
                                    @foreach($this->documents as $document)
                                        <li wire:key="document-{{ $document->id }}">
                                            <a href="{{ $document->getUrl() }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-lg border border-bordure bg-casse px-3 py-2 text-sm text-anthracite transition-colors hover:border-cuivre hover:text-cuivre">
                                                <svg class="size-5 shrink-0 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                <span class="flex-1 truncate">{{ $document->name }}</span>
                                                <span class="text-xs text-ardoise">{{ $document->human_readable_size }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="on-dark relative overflow-hidden rounded-lg bg-nuit p-6 text-white lg:p-8">
                    <div class="pointer-events-none absolute -left-24 -top-24 size-72 rounded-full bg-cuivre/10 blur-3xl" aria-hidden="true"></div>
                    <div class="relative">
                        <div class="font-display text-xs font-semibold uppercase tracking-[0.22em] text-cuivre">Un projet similaire ?</div>
                        <h2 class="mt-3 font-display text-2xl font-extrabold tracking-tight">Parlons de votre projet.</h2>
                        <p class="mt-3 text-sm leading-relaxed text-white/70">L’équipe SIBEA vous accompagne de l’étude à la réalisation.</p>
                    </div>
                    <div class="relative mt-6">
                        <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-full bg-cuivre px-5 py-2.5 font-display text-sm font-semibold text-nuit transition-colors duration-300 hover:bg-white" wire:navigate>
                            Nous contacter
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    @if($this->gallery->isNotEmpty())
        <section class="border-y border-bordure bg-surface">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20" x-data="{ lightboxOpen: false, lightboxImage: '', lightboxAlt: '' }" x-on:keydown.escape.window="lightboxOpen = false">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">EN IMAGES</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl mb-8">Galerie</h2>

                <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4">
                    @foreach($this->gallery as $media)
                        <button
                            type="button"
                            wire:key="media-{{ $media->id }}"
                            x-on:click="lightboxOpen = true; lightboxImage = @js($media->getUrl('medium')); lightboxAlt = @js($media->name ?: $project->title)"
                            aria-label="Agrandir la photo : {{ $media->name ?: $project->title }}"
                            class="group relative aspect-[4/3] overflow-hidden rounded-lg border border-bordure bg-nuit focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre focus-visible:ring-offset-2"
                        >
                            <img src="{{ $media->getUrl('thumb') }}" alt="{{ $media->name ?: $project->title }}" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                        </button>
                    @endforeach
                </div>

                <div
                    x-show="lightboxOpen"
                    x-transition.opacity.duration.200ms
                    style="display: none;"
                    x-on:click.self="lightboxOpen = false"
                    class="fixed inset-0 z-[70] flex items-center justify-center bg-nuit/95 p-4 backdrop-blur-sm"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Galerie photo"
                >
                    <img :src="lightboxImage" :alt="lightboxAlt" class="max-h-full max-w-5xl rounded-lg object-contain">
                    <button
                        type="button"
                        x-on:click="lightboxOpen = false"
                        aria-label="Fermer la galerie"
                        class="absolute right-5 top-5 inline-flex size-11 items-center justify-center rounded-full bg-white/10 text-2xl leading-none text-white transition-colors hover:bg-cuivre hover:text-nuit"
                    >✕</button>
                </div>
            </div>
        </section>
    @endif

    @if($hasCoordinates)
        <section class="border-b border-bordure">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">LOCALISATION</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl mb-8">{{ $project->location ?: $project->title }}</h2>
                <div class="overflow-hidden rounded-lg border border-bordure">
                    <iframe
                        src="https://www.openstreetmap.org/export/embed.html?bbox={{ $project->longitude - 0.01 }}%2C{{ $project->latitude - 0.01 }}%2C{{ $project->longitude + 0.01 }}%2C{{ $project->latitude + 0.01 }}&layer=mapnik&marker={{ $project->latitude }}%2C{{ $project->longitude }}"
                        width="100%"
                        height="380"
                        style="border:0;"
                        loading="lazy"
                        title="Localisation de {{ $project->title }}">
                    </iframe>
                </div>
            </div>
        </section>
    @endif

    @if($this->relatedProjects->isNotEmpty())
        <section class="border-b border-bordure bg-surface">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">POUR ALLER PLUS LOIN</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl mb-8">Réalisations similaires</h2>

                <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($this->relatedProjects as $related)
                        @php($relatedCover = $related->getFirstMediaUrl('cover', 'thumb'))
                        <a wire:key="related-project-{{ $related->id }}" href="{{ route('projects.show', $related->slug) }}" class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md" wire:navigate>
                            <div class="relative h-56 overflow-hidden bg-nuit">
                                @if($relatedCover)
                                    <img src="{{ $relatedCover }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                                @else
                                    <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;" aria-hidden="true"></div>
                                    <span class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5" aria-hidden="true">{{ mb_substr($related->title, 0, 1) }}</span>
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10" aria-hidden="true"></div>
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                @if($related->sectors->isNotEmpty())
                                    <div class="mb-3 flex flex-wrap gap-2">
                                        @foreach($related->sectors as $sector)
                                            <span class="rounded-full bg-cuivre/10 px-3 py-1 text-[11px] font-display font-semibold uppercase tracking-wider text-cuivre">{{ $sector->name }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                <h3 class="font-display text-xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">{{ $related->title }}</h3>
                                @if($related->short_description)
                                    <p class="mt-3 text-sm leading-relaxed text-ardoise line-clamp-2">{{ $related->short_description }}</p>
                                @endif
                                <div class="mt-auto pt-6">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 group-hover:bg-cuivre group-hover:text-nuit">
                                        Voir
                                        <span class="inline-block transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($this->previousProject || $this->nextProject)
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12">
            <div class="flex flex-col gap-6 border-t border-bordure pt-8 sm:flex-row sm:items-center sm:justify-between">
                @if($this->previousProject)
                    <a href="{{ route('projects.show', $this->previousProject->slug) }}" class="group inline-flex items-center gap-3" wire:navigate>
                        <span aria-hidden="true" class="text-lg text-cuivre transition-transform duration-300 motion-safe:group-hover:-translate-x-1">←</span>
                        <span>
                            <span class="block font-display text-xs font-semibold uppercase tracking-wider text-ardoise/70">Réalisation précédente</span>
                            <span class="font-display font-bold text-nuit transition-colors duration-300 group-hover:text-cuivre">{{ $this->previousProject->title }}</span>
                        </span>
                    </a>
                @else
                    <span aria-hidden="true"></span>
                @endif

                @if($this->nextProject)
                    <a href="{{ route('projects.show', $this->nextProject->slug) }}" class="group inline-flex items-center gap-3 text-left sm:ml-auto sm:text-right" wire:navigate>
                        <span>
                            <span class="block font-display text-xs font-semibold uppercase tracking-wider text-ardoise/70">Réalisation suivante</span>
                            <span class="font-display font-bold text-nuit transition-colors duration-300 group-hover:text-cuivre">{{ $this->nextProject->title }}</span>
                        </span>
                        <span aria-hidden="true" class="text-lg text-cuivre transition-transform duration-300 motion-safe:group-hover:translate-x-1">→</span>
                    </a>
                @endif
            </div>
        </div>
    @endif
</div>
