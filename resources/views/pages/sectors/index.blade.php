<?php

use App\Models\Sector;
use App\Support\PageSeo;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public function mount(): void
    {
        PageSeo::share((string) setting('sectors.hero.title'), setting('sectors.hero.subtitle'));
    }

    #[Computed]
    public function sectors()
    {
        return Sector::active()
            ->ordered()
            ->with([
                'expertises' => fn ($query) => $query->where('is_active', true)->ordered()
                    ->with(['services' => fn ($services) => $services->where('is_active', true)->ordered()]),
            ])
            ->withCount([
                'projects' => fn ($query) => $query->published(),
            ])
            ->get();
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        <img src="{{ setting_media_url('visuals.hero.sectors') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-14">
            <nav aria-label="Fil d’Ariane" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/60">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-cuivre" wire:navigate>Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-white/90" aria-current="page">{{ setting('sectors.hero.title') }}</li>
                </ol>
            </nav>
            <h1 class="font-display font-extrabold text-4xl lg:text-6xl mb-6 max-w-4xl">{{ setting('sectors.hero.title') }}</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">{{ setting('sectors.hero.subtitle') }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-8">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            @foreach($this->sectors as $sector)
                @php($position = str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT))
                @php($hero = $sector->getFirstMediaUrl('hero', 'thumb'))
                @php($services = $sector->expertises->flatMap->services->unique('id')->values())
                @php($remainingExpertises = $sector->expertises->count() - 6)
                @php($remainingServices = $services->count() - 6)
                <article
                    wire:key="sector-{{ $sector->id }}"
                    class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md"
                >
                    <div class="relative h-56 overflow-hidden bg-nuit lg:h-64">
                        @if($hero)
                            <img src="{{ $hero }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                        @else
                            <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;" aria-hidden="true"></div>
                            <span class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5" aria-hidden="true">{{ mb_substr($sector->name, 0, 1) }}</span>
                        @endif
                        <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10" aria-hidden="true"></div>

                        <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                            <span class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">Secteur d’activité</span>
                            <span class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col p-6 lg:p-8">
                        <h2 class="font-display text-2xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">{{ $sector->name }}</h2>

                        @if($sector->hero_title)
                            <p class="mt-3 font-display text-lg font-bold leading-snug text-anthracite">{{ $sector->hero_title }}</p>
                        @endif

                        @if($sector->hero_description ?? $sector->short_description ?? $sector->description)
                            <p class="mt-3 text-sm leading-relaxed text-ardoise">{{ $sector->hero_description ?? $sector->short_description ?? $sector->description }}</p>
                        @endif

                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-6">
                            <a
                                href="{{ route('sectors.show', $sector->slug) }}"
                                class="inline-flex items-center gap-2 font-display text-sm font-semibold text-cuivre transition-colors hover:text-nuit focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre focus-visible:ring-offset-2"
                             wire:navigate>
                                Découvrir le secteur
                                <span aria-hidden="true">→</span>
                            </a>
                            <a
                                href="{{ route('contact') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 hover:bg-cuivre hover:text-nuit focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre focus-visible:ring-offset-2"
                             wire:navigate>
                                Nous contacter
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
