<?php

use App\Models\Sector;
use App\Support\PageSeo;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Sector $sector;

    public function mount(): void
    {
        abort_unless($this->sector->is_active, 404);

        $description = Str::squish((string) ($this->sector->description ?: $this->sector->short_description));

        PageSeo::share($this->sector->meta_title ?: $this->sector->name, $this->sector->meta_description ?: ($description !== '' ? $description : null));
        View::share('ogImage', $this->sector->getFirstMediaUrl('hero', 'og') ?: null);
    }

    #[Computed]
    public function projects()
    {
        return $this->sector->projects()
            ->published()
            ->with('sectors')
            ->latest()
            ->take(3)
            ->get();
    }

    #[Computed]
    public function totalProjects(): int
    {
        return $this->sector->projects()->published()->count();
    }
};
?>

@php($hero = $sector->getFirstMediaUrl('hero', 'medium') ?: setting_media_url('visuals.hero.sectors'))

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        <img src="{{ $hero }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" decoding="async">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.94) 0%, rgba(11,31,51,0.68) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-14">
            <nav aria-label="Fil d’Ariane" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/60">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-cuivre" wire:navigate>Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li><a href="{{ route('sectors.index') }}" class="transition-colors hover:text-cuivre" wire:navigate>{{ setting('sectors.hero.title') }}</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-white/90" aria-current="page">{{ $sector->name }}</li>
                </ol>
            </nav>

            <h1 class="font-display font-extrabold text-4xl lg:text-6xl mb-6 max-w-4xl">
                {{ $sector->hero_title ?: $sector->name }}
            </h1>

            @if($sector->hero_description)
                <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                    {{ $sector->hero_description }}
                </p>
            @endif

            @if($sector->hero_cta_label)
                <div class="mt-8">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-full bg-cuivre px-5 py-2.5 font-display text-sm font-semibold text-nuit transition-colors duration-300 hover:bg-white" wire:navigate>
                        {{ $sector->hero_cta_label }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            @endif
        </div>
    </section>

    @if($sector->page_intro_title || $sector->page_intro_text || ! empty($sector->page_figures))
        <section class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <div class="grid gap-12 lg:grid-cols-3 lg:gap-16">
                <div class="lg:col-span-2">
                    @if($sector->page_intro_title)
                        <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl mb-5">{{ $sector->page_intro_title }}</h2>
                    @endif

                    @if($sector->page_intro_text)
                        <div class="space-y-4 text-ardoise leading-relaxed">
                            @foreach(preg_split('/\R{2,}/', trim($sector->page_intro_text)) as $paragraph)
                                <p>{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if(! empty($sector->page_figures))
                    <div class="grid grid-cols-2 gap-4 self-start">
                        @foreach($sector->page_figures as $index => $figure)
                            <div wire:key="figure-{{ $index }}" class="rounded-lg border border-bordure bg-surface p-5">
                                <div class="font-display text-3xl font-extrabold leading-none text-cuivre">{{ $figure['value'] }}</div>
                                <div class="mt-2 text-sm leading-relaxed text-ardoise">{{ $figure['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if(! empty($sector->page_cards))
        <section class="border-y border-bordure bg-surface">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">NOS DOMAINES D’EXPERTISE</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl mb-8">Ce que nous faisons dans ce secteur.</h2>

                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach($sector->page_cards as $index => $card)
                        <div wire:key="card-{{ $index }}" class="rounded-lg border border-bordure bg-surface p-6">
                            <h3 class="font-display text-lg font-bold text-nuit">{{ $card['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-ardoise">{{ $card['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($this->projects->isNotEmpty())
        <section class="max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">RÉALISATIONS</div>
                    <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl">Nos réalisations dans ce secteur.</h2>
                </div>
                @if($this->totalProjects > 3)
                    <a href="{{ route('projects.index', ['sector_id' => $sector->id]) }}" class="font-display text-sm font-semibold text-cuivre transition-colors hover:text-nuit" wire:navigate>
                        Voir toutes les réalisations →
                    </a>
                @endif
            </div>

            <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                @foreach($this->projects as $project)
                    @php($position = str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT))
                    @php($cover = $project->getFirstMediaUrl('cover', 'thumb'))
                    <a wire:key="project-{{ $project->id }}" href="{{ route('projects.show', $project->slug) }}" class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md" wire:navigate>
                        <div class="relative h-56 overflow-hidden bg-nuit">
                            @if($cover)
                                <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
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
                        <div class="flex flex-1 flex-col p-6">
                            <h3 class="font-display text-xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">{{ $project->title }}</h3>
                            @if($project->short_description)
                                <p class="mt-3 text-sm leading-relaxed text-ardoise line-clamp-2">{{ $project->short_description }}</p>
                            @endif
                            <div class="mt-auto pt-6">
                                <span class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 group-hover:bg-cuivre group-hover:text-nuit">
                                    Voir l’étude de cas
                                    <span class="inline-block transition-transform group-hover:translate-x-1" aria-hidden="true">→</span>
                                </span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="border-y border-bordure bg-casse">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 flex flex-wrap items-center justify-between gap-6">
            <div>
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">ENGAGEMENTS</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl">Sécurité, qualité et impact local.</h2>
            </div>
            <a href="{{ route('pages.show', ['page' => 'engagements']) }}" class="inline-flex items-center gap-2 rounded-full bg-nuit px-5 py-2.5 font-display text-sm font-semibold text-white transition-colors duration-300 hover:bg-cuivre hover:text-nuit" wire:navigate>
                Nos engagements
                <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>

    @if($sector->page_cta_title || $sector->page_cta_label)
        <section class="on-dark relative overflow-hidden bg-nuit text-white">
            <div class="pointer-events-none absolute -left-24 -top-24 size-72 rounded-full bg-cuivre/10 blur-3xl" aria-hidden="true"></div>
            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-16 lg:py-20 text-center">
                <h2 class="font-display font-extrabold tracking-tight text-3xl lg:text-4xl mb-4">{{ $sector->page_cta_title }}</h2>
                @if($sector->page_cta_text)
                    <p class="text-white/70 text-lg max-w-2xl mx-auto mb-8">{{ $sector->page_cta_text }}</p>
                @endif
                <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-full bg-cuivre px-5 py-2.5 font-display text-sm font-semibold text-nuit transition-colors duration-300 hover:bg-white" wire:navigate>
                    {{ $sector->page_cta_label ?: 'Nous contacter' }}
                    <span aria-hidden="true">→</span>
                </a>
            </div>
        </section>
    @endif
</div>
