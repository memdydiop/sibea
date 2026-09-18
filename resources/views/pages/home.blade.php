<?php

use App\Models\Expertise;
use App\Models\Project;
use App\Models\Sector;
use App\Models\Statistic;
use App\Models\Testimonial;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {
    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->withCount('expertises')->get();
    }

    #[Computed]
    public function expertises()
    {
        return Expertise::active()
            ->ordered()
            ->with(['services' => fn($query) => $query->active()->ordered()])
            ->get();
    }

    #[Computed]
    public function projects()
    {
        return Project::published()->with('sectors')->latest()->take(3)->get();
    }

    #[Computed]
    public function testimonials()
    {
        return Testimonial::active()->ordered()->take(3)->get();
    }

    #[Computed]
    public function statistics()
    {
        return Statistic::active()->ordered()->get();
    }
};
?>

<div>
    <div>
        <section class="on-dark relative bg-nuit text-white overflow-hidden">
            <img src="{{ setting_media_url('visuals.hero.home', 'hero') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" decoding="async" fetchpriority="high">
            <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.94) 0%, rgba(11,31,51,0.68) 100%);"></div>
            <div class="relative max-w-7xl mx-auto px-6 lg:px-8 py-20 lg:py-28 lg:min-h-[60vh] flex items-center">
                <div class="max-w-2xl">
                    <div class="text-cuivre font-display font-semibold tracking-widest text-sm mb-6">
                        {{ setting('general.site_name') }}
                    </div>
                    <h1 class="font-display font-extrabold text-3xl lg:text-6xl leading-tight mb-6">
                        {{ setting('home.hero.title') }}
                    </h1>
                    <p class="text-white/80 text-lg leading-relaxed mb-10">
                        {{ setting('home.hero.subtitle') }}
                    </p>
                    <flux:button href="{{ route('sectors.index') }}" variant="primary" class="bg-cuivre!">
                        {{ setting('home.hero.primary_label') }}
                    </flux:button>
                </div>
            </div>
        </section>

        <section class="bg-surface">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                <div class="grid gap-12 lg:grid-cols-2 lg:gap-16 lg:items-center">
                    <div class="overflow-hidden rounded-lg border border-bordure bg-surface">
                        <img src="{{ setting_media_url('visuals.group', 'hero') }}" alt="{{ setting('home.group.title') }}" loading="lazy" decoding="async" class="h-72 w-full object-cover lg:h-96">
                    </div>
                    <div>
                        <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">LE GROUPE</div>
                        <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-4xl mb-5">{{ setting('home.group.title') }}</h2>
                        <p class="text-ardoise text-lg leading-relaxed mb-6">{{ setting('home.group.text') }}</p>
                        @if(setting('home.group.quote'))
                            <figure class="mb-8 border-l-2 border-cuivre pl-5">
                                <blockquote class="font-display text-lg italic leading-relaxed text-anthracite">« {{ setting('home.group.quote') }} »</blockquote>
                                <figcaption class="mt-2 text-sm text-ardoise">Le Président Directeur Général</figcaption>
                            </figure>
                        @endif
                        <a href="{{ route('pages.show', ['page' => 'le-groupe']) }}" class="inline-flex items-center gap-2 rounded-full bg-nuit px-5 py-2.5 font-display text-sm font-semibold text-white transition-colors duration-300 hover:bg-cuivre hover:text-nuit">
                            {{ setting('home.group.button') }}
                            <span aria-hidden="true">→</span>
                        </a>
                    </div>
                </div>
            </div>
        </section>

        @if ($this->statistics->isNotEmpty())
            <section class="on-dark bg-nuit text-white border-t border-white/10">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12 grid grid-cols-2 lg:grid-cols-4 gap-8">
                    @foreach ($this->statistics as $statistic)
                        <div wire:key="statistic-{{ $statistic->id }}" class="text-center">
                            <div class="font-display font-extrabold text-4xl lg:text-5xl text-cuivre">
                                {{ $statistic->value }}</div>
                            <div class="text-sm text-white/70 mt-1">{{ $statistic->label }}</div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="bg-casse">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">NOS SECTEURS</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-5xl mb-4">
                    {{ setting('home.sectors.title') }}</h2>
                <p class="text-ardoise text-lg mb-8">{{ setting('home.sectors.subtitle') }}</p>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($this->sectors as $index => $sector)
                        @php($position = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))
                        @php($hero = $sector->getFirstMediaUrl('hero', 'thumb'))
                        <a wire:key="sector-{{ $sector->id }}" href="{{ route('sectors.show', $sector->slug) }}"
                            class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md">
                            <div class="relative h-56 overflow-hidden bg-nuit lg:h-64">
                                @if ($hero)
                                    <img src="{{ $hero }}" alt="{{ $sector->name }}" loading="lazy" decoding="async"
                                        class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                                @else
                                    <div class="absolute inset-0 opacity-[0.07]"
                                        style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;"
                                        aria-hidden="true"></div>
                                    <span
                                        class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5"
                                        aria-hidden="true">{{ mb_substr($sector->name, 0, 1) }}</span>
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10"
                                    aria-hidden="true"></div>
                                <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                                    <span
                                        class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">Secteur
                                        d’activité</span>
                                    <span
                                        class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-6 lg:p-8">
                                <h3
                                    class="font-display text-2xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">
                                    {{ $sector->name }}</h3>
                                <p class="mt-3 text-sm leading-relaxed text-ardoise">{{ $sector->short_description }}
                                </p>
                                <div class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-6">
                                    <span
                                        class="text-xs font-display font-semibold uppercase tracking-wider text-ardoise/70">
                                        {{ $sector->expertises_count }}
                                        expertise{{ $sector->expertises_count > 1 ? 's' : '' }}
                                    </span>
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 group-hover:bg-cuivre group-hover:text-nuit">
                                        Découvrir
                                        <span aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="bg-surface border-y border-bordure">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">NOS EXPERTISES</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-5xl mb-4">
                    {{ setting('home.expertises.title') }}</h2>
                <p class="text-ardoise text-lg mb-8">{{ setting('home.expertises.subtitle') }}</p>
                <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($this->expertises as $index => $expertise)
                        @php($position = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))
                        @php($cover = $expertise->getFirstMediaUrl('cover', 'thumb'))
                        <a wire:key="expertise-{{ $expertise->id }}" href="{{ route('expertises.index') }}"
                            class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md">
                            <div class="relative h-56 overflow-hidden bg-nuit lg:h-64">
                                @if ($cover)
                                    <img src="{{ $cover }}" alt="{{ $expertise->name }}" loading="lazy" decoding="async"
                                        class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                                @else
                                    <div class="absolute inset-0 opacity-[0.07]"
                                        style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;"
                                        aria-hidden="true"></div>
                                    <span
                                        class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5"
                                        aria-hidden="true">{{ mb_substr($expertise->name, 0, 1) }}</span>
                                @endif
                                <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10"
                                    aria-hidden="true"></div>
                                <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                                    <span
                                        class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">Expertise</span>
                                    <span
                                        class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                                </div>
                            </div>
                            <div class="flex flex-1 flex-col p-6 lg:p-8">
                                <h3
                                    class="font-display text-2xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">
                                    {{ $expertise->name }}</h3>
                                <p class="mt-3 text-sm leading-relaxed text-ardoise">
                                    {{ $expertise->short_description }}</p>
                                @if ($expertise->services->isNotEmpty())
                                    <ul class="mt-4 text-sm text-anthracite space-y-1.5">
                                        @foreach ($expertise->services->take(4) as $service)
                                            <li class="flex gap-2"><span
                                                    class="text-cuivre">•</span>{{ $service->name }}
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                                <div class="mt-auto pt-6">
                                    <span
                                        class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 group-hover:bg-cuivre group-hover:text-nuit">
                                        En savoir plus
                                        <span class="inline-block transition-transform group-hover:translate-x-1"
                                            aria-hidden="true">→</span>
                                    </span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                    <div class="on-dark group relative flex flex-col overflow-hidden rounded-lg bg-nuit text-white">
                        <div class="relative h-56 overflow-hidden lg:h-64">
                            <div class="absolute inset-0 opacity-[0.07]"
                                style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;"
                                aria-hidden="true"></div>
                            <div class="pointer-events-none absolute -left-16 -top-20 size-64 rounded-full bg-cuivre/15 blur-3xl"
                                aria-hidden="true"></div>
                            <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10" aria-hidden="true"></div>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <svg class="size-16 text-cuivre" fill="none" stroke="currentColor" stroke-width="1.5"
                                    viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 1.53.468 2.965 1.276 4.19L3 20.25l4.5-1.5A9.72 9.72 0 0 0 12 20.25Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm3.75 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                            <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                                <span
                                    class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">{{ setting('home.expertise_cta.eyebrow') }}</span>
                            </div>
                        </div>
                        <div class="flex flex-1 flex-col p-6 lg:p-8">
                            <h3 class="font-display text-2xl font-extrabold leading-snug tracking-tight">{{ setting('home.expertise_cta.title') }}</h3>
                            <p class="mt-3 text-sm leading-relaxed text-white/70">{{ setting('home.expertise_cta.subtitle') }}</p>
                            @php($points = collect(preg_split('/\R/', (string) setting('home.expertise_cta.points')))->map(fn ($point) => trim($point))->filter())
                            @if($points->isNotEmpty())
                                <ul class="mt-4 space-y-1.5 text-sm text-white/70">
                                    @foreach($points as $point)
                                        <li class="flex gap-2"><span class="text-cuivre" aria-hidden="true">•</span>{{ $point }}</li>
                                    @endforeach
                                </ul>
                            @endif
                            <div class="mt-auto flex flex-wrap items-center gap-3 pt-6">
                                <a href="{{ route('contact') }}"
                                    class="inline-flex items-center gap-2 rounded-full bg-cuivre px-4 py-2 font-display text-sm font-semibold text-nuit transition-colors duration-300 hover:bg-white">
                                    Nous contacter
                                    <span aria-hidden="true">→</span>
                                </a>
                                @if(setting('contact.whatsapp'))
                                    <a href="https://wa.me/{{ setting('contact.whatsapp') }}?text={{ urlencode(setting('contact.whatsapp_message')) }}"
                                        target="_blank" rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-full border border-white/20 px-4 py-2 font-display text-sm font-semibold text-white/90 transition-colors duration-300 hover:border-[#25D366] hover:text-[#25D366]">
                                        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                                        {{ setting('home.expertise_cta.whatsapp_label') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="bg-casse">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                <div class="flex flex-wrap items-end justify-between gap-4 mb-12">
                    <div>
                        <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">RÉALISATIONS
                        </div>
                        <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-5xl">
                            {{ setting('home.projects.title') }}</h2>
                    </div>
                    <a href="{{ route('projects.index') }}"
                        class="text-sm font-display font-semibold text-cuivre">Voir toutes les réalisations →</a>
                </div>
                @if ($this->projects->isNotEmpty())
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach ($this->projects as $index => $project)
                            @php($position = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT))
                            @php($cover = $project->getFirstMediaUrl('cover', 'thumb'))
                            @php($meta = collect([$project->location, $project->project_date?->format('Y')])->filter())
                            <a wire:key="project-{{ $project->id }}"
                                href="{{ route('projects.show', $project->slug) }}"
                                class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md">
                                <div class="relative h-56 overflow-hidden bg-nuit lg:h-64">
                                    @if ($cover)
                                        <img src="{{ $cover }}" alt="{{ $project->title }}" loading="lazy"
                                            decoding="async"
                                            class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                                    @else
                                        <div class="absolute inset-0 opacity-[0.07]"
                                            style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;"
                                            aria-hidden="true"></div>
                                        <span
                                            class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5"
                                            aria-hidden="true">{{ mb_substr($project->title, 0, 1) }}</span>
                                    @endif
                                    <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10"
                                        aria-hidden="true"></div>
                                    <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                                        @if ($project->status)
                                            <span
                                                class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm">{{ $project->status->label() }}</span>
                                        @else
                                            <span aria-hidden="true"></span>
                                        @endif
                                        <span
                                            class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                                    </div>
                                </div>
                                <div class="flex flex-1 flex-col p-6 lg:p-8">
                                    @if ($project->sectors->isNotEmpty())
                                        <div class="mb-3 flex flex-wrap gap-2">
                                            @foreach ($project->sectors as $sector)
                                                <span
                                                    class="rounded-full bg-cuivre/10 px-3 py-1 text-[11px] font-display font-semibold uppercase tracking-wider text-cuivre">{{ $sector->name }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                    <h3
                                        class="font-display text-2xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">
                                        {{ $project->title }}</h3>
                                    @if ($project->short_description)
                                        <p class="mt-3 text-sm leading-relaxed text-ardoise">
                                            {{ $project->short_description }}</p>
                                    @endif
                                    <div class="mt-auto flex flex-wrap items-center gap-3 pt-6">
                                        @if ($meta->isNotEmpty())
                                            <span class="text-xs text-ardoise">{{ $meta->implode(' · ') }}</span>
                                        @endif
                                        <span
                                            class="ml-auto inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 group-hover:bg-cuivre group-hover:text-nuit">
                                            Lire l’étude de cas
                                            <span class="inline-block transition-transform group-hover:translate-x-1"
                                                aria-hidden="true">→</span>
                                        </span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-ardoise">Nos premières réalisations seront publiées prochainement.</p>
                @endif
            </div>
        </section>

        <section class="bg-surface border-y border-bordure">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">NOTRE MÉTHODE</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-5xl mb-12 max-w-3xl">
                    {{ setting('home.method.title') }}</h2>
                @php($steps = setting_array('home.method_steps'))
                <div class="grid md:grid-cols-3 gap-10 mb-12">
                    @foreach ($steps as $index => $step)
                        <div wire:key="method-step-{{ $index }}">
                            <div class="font-display font-extrabold text-4xl text-accroche mb-4">
                                {{ sprintf('%02d', $index + 1) }}</div>
                            <h3 class="font-display font-bold text-xl mb-2 text-nuit">{{ $step['title'] }}</h3>
                            <p class="text-sm text-ardoise leading-relaxed">{{ $step['text'] }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('expertises.index') }}"
                        class="text-sm font-display font-semibold text-accroche">Découvrir nos expertises →</a>
                </div>
            </div>
        </section>

        <section class="on-dark bg-nuit text-white">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20">
                <div class="grid gap-10 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div class="max-w-3xl">
                        <div class="text-foret-clair font-display font-semibold tracking-widest text-sm mb-4">ENGAGEMENTS</div>
                        <h2 class="font-display font-extrabold tracking-tight text-3xl lg:text-4xl mb-5">{{ setting('home.rse.title') }}</h2>
                        <p class="text-white/70 text-lg leading-relaxed">{{ setting('home.rse.text') }}</p>
                    </div>
                    <a href="{{ route('pages.show', ['page' => 'engagements']) }}" class="inline-flex items-center gap-2 self-start rounded-full bg-foret px-5 py-2.5 font-display text-sm font-semibold text-white transition-colors duration-300 hover:bg-white hover:text-nuit">
                        {{ setting('home.rse.button') }}
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </section>

        @if ($this->testimonials->isNotEmpty())
            <section class="bg-casse">
                <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                    <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">TÉMOIGNAGES
                    </div>
                    <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-5xl mb-12">
                        {{ setting('home.testimonials.title') }}</h2>
                    <div class="grid md:grid-cols-3 gap-6">
                        @foreach ($this->testimonials as $testimonial)
                            <figure wire:key="testimonial-{{ $testimonial->id }}"
                                class="border border-bordure rounded-lg p-6 lg:p-8 bg-surface flex flex-col">
                                <div class="text-cuivre tracking-widest mb-3" aria-label="Note : 5 sur 5">★★★★★
                                </div>
                                <blockquote class="text-sm text-anthracite leading-relaxed mb-6">«
                                    {{ $testimonial->content }} »</blockquote>
                                <figcaption class="mt-auto flex items-center gap-3">
                                    <span
                                        class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-nuit text-white font-display font-bold"
                                        aria-hidden="true">
                                        {{ mb_strtoupper(mb_substr($testimonial->author_name, 0, 1)) }}
                                    </span>
                                    <span>
                                        <span
                                            class="block font-display font-semibold text-sm">{{ $testimonial->author_name }}</span>
                                        @if ($testimonial->position || $testimonial->organization)
                                            <span
                                                class="block text-xs text-ardoise">{{ trim($testimonial->position . ' — ' . $testimonial->organization, ' —') }}</span>
                                        @endif
                                    </span>
                                </figcaption>
                            </figure>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        <section class="bg-surface border-y border-bordure">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24 text-center">
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-5xl mb-4">
                    {{ setting('home.cta.title') }}</h2>
                <p class="text-ardoise text-lg max-w-2xl mx-auto mb-10">{{ setting('home.cta.subtitle') }}</p>
                <div class="flex flex-wrap justify-center gap-4">
                    <flux:button variant="primary" href="{{ route('contact') }}" class="!bg-cuivre">
                        {{ setting('home.cta.button') }}
                    </flux:button>
                    <flux:button href="{{ route('projects.index') }}" variant="outline">
                        Voir nos réalisations
                    </flux:button>
                </div>
            </div>
        </section>
    </div>
