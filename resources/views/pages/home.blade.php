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
        return Testimonial::active()->ordered()->take(6)->get();
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
    <section
        x-data="{
            current: 0,
            paused: false,
            isMobile: window.innerWidth < 1024,
            autoplay: !window.matchMedia('(prefers-reduced-motion: reduce)').matches &&
                window.innerWidth >= 1024,
            timer: null,
            touchStartX: 0,
            hues: ['#164E70', '#1E3A5F', '#0E4D3C', '#5B3A1E'],
            sectors: @js(
    $this->sectors->map(
        fn($s) => [
            'name' => $s->name,
            'title' => $s->hero_title,
            'description' => $s->hero_description,
            'cta' => $s->hero_cta_label ?: 'Découvrir le secteur',
            'scene' => match ($s->slug) {
                'btp' => 'crane',
                'immobilier' => 'city',
                'energie' => 'sun',
                'agro-industrie' => 'field',
                default => 'city',
            },
            'image' => $s->getFirstMediaUrl('hero'),
            'url' => route('sectors.show', $s->slug),
        ],
    ),
),
            start() {
                if (!this.autoplay || !this.sectors.length) return;
                this.timer = setInterval(() => {
                    if (!this.paused) this.current = (this.current + 1) % this.sectors.length;
                }, 7000);
            },
            destroy() { if (this.timer) clearInterval(this.timer); },
            next() {
                if (!this.sectors.length) return;
                this.current = (this.current + 1) % this.sectors.length;
            },
            prev() {
                if (!this.sectors.length) return;
                this.current = (this.current - 1 + this.sectors.length) % this.sectors.length;
            },
            goTo(i) {
                if (!this.sectors.length) return;
                this.current = i;
            },
            onTouchStart(e) { this.touchStartX = e.touches[0].clientX; },
            onTouchEnd(e) {
                const delta = e.changedTouches[0].clientX - this.touchStartX;
                if (Math.abs(delta) > 50) delta < 0 ? this.next() : this.prev();
            }
        }" x-init="start()" x-on:mouseenter="paused = true"
            x-on:mouseleave="paused = false" x-on:focusin="paused = true" x-on:focusout="paused = false"
            x-on:keydown.arrow-left.window="prev()" x-on:keydown.arrow-right.window="next()"
            x-on:touchstart="onTouchStart($event)" x-on:touchend="onTouchEnd($event)"
            x-on:livewire:navigating.window="destroy()"
            class="relative min-h-[50vh] bg-nuit text-white overflow-hidden">
        <div class="absolute inset-0" aria-hidden="true">
            <template x-for="(sector, index) in sectors" :key="'bg-' + index">
                <div x-show="current === index" x-transition.opacity.duration.700ms class="absolute inset-0">
                    <div class="absolute inset-0"
                        :style="`background: linear-gradient(135deg, ${hues[index % hues.length]} 0%, #0B1F33 100%)`">
                    </div>
                    <div class="absolute inset-0 opacity-20"
                        style="background-image: radial-gradient(rgba(255,255,255,.35) 1px, transparent 1px); background-size: 22px 22px;">
                    </div>
                    <template x-if="sector.image">
                        <img :src="sector.image" :alt="sector.name"
                            class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                    </template>
                    <div class="absolute inset-0"
                        style="background: linear-gradient(90deg, rgba(11,31,51,0.93) 0%, rgba(11,31,51,0.55) 50%, rgba(11,31,51,0.30) 100%);">
                    </div>
                    <template x-if="sector.scene === 'crane'">
                        <svg class="absolute bottom-0 left-0 h-44 w-full text-white/10" viewBox="0 0 800 200"
                            preserveAspectRatio="xMidYMax slice" fill="currentColor" aria-hidden="true">
                            <rect x="60" y="120" width="120" height="80" />
                            <rect x="200" y="90" width="90" height="110" />
                            <rect x="620" y="110" width="140" height="90" />
                            <rect x="392" y="20" width="10" height="180" />
                            <rect x="250" y="20" width="300" height="8" />
                            <rect x="250" y="28" width="6" height="60" />
                            <rect x="236" y="88" width="34" height="22" />
                            <rect x="402" y="4" width="60" height="16" />
                        </svg>
                    </template>
                    <template x-if="sector.scene === 'city'">
                        <svg class="absolute bottom-0 left-0 h-44 w-full text-white/10" viewBox="0 0 800 200"
                            preserveAspectRatio="xMidYMax slice" fill="currentColor" aria-hidden="true">
                            <rect x="80" y="70" width="110" height="130" />
                            <rect x="210" y="30" width="130" height="170" />
                            <rect x="360" y="90" width="90" height="110" />
                            <rect x="470" y="50" width="150" height="150" />
                            <rect x="640" y="100" width="100" height="100" />
                        </svg>
                    </template>
                    <template x-if="sector.scene === 'sun'">
                        <svg class="absolute bottom-0 left-0 h-44 w-full text-white/10" viewBox="0 0 800 200"
                            preserveAspectRatio="xMidYMax slice" fill="currentColor" aria-hidden="true">
                            <circle cx="640" cy="56" r="34" />
                            <g stroke="currentColor" stroke-width="8" stroke-linecap="round">
                                <line x1="640" y1="4" x2="640" y2="14" />
                                <line x1="640" y1="98" x2="640" y2="108" />
                                <line x1="588" y1="56" x2="598" y2="56" />
                                <line x1="682" y1="56" x2="692" y2="56" />
                            </g>
                            <polygon points="120,200 230,200 195,128 85,128" />
                            <polygon points="250,200 360,200 325,128 215,128" />
                        </svg>
                    </template>
                    <template x-if="sector.scene === 'field'">
                        <svg class="absolute bottom-0 left-0 h-44 w-full text-white/10" viewBox="0 0 800 200"
                            preserveAspectRatio="xMidYMax slice" fill="currentColor" aria-hidden="true">
                            <circle cx="650" cy="48" r="26" />
                            <path d="M0 148 Q 200 118 400 148 T 800 148 L800 200 L0 200 Z" />
                            <path d="M0 170 Q 200 144 400 170 T 800 170 L800 200 L0 200 Z" opacity=".6" />
                        </svg>
                    </template>
                    <div class="absolute -bottom-8 right-4 font-[Manrope] font-extrabold text-[10rem] leading-none text-white/10 select-none"
                        aria-hidden="true" x-text="String(index + 1).padStart(2, '0')"></div>
                </div>
            </template>
        </div>

        <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 pt-36 pb-32 grid lg:grid-cols-2 gap-12 items-center min-h-[60vh]">
            <div class="max-w-xl">
                <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-6">
                    GROUPE SIBEA
                </div>
                <h1 class="font-[Manrope] font-extrabold text-4xl lg:text-6xl leading-tight mb-6">
                    Des expertises solides pour construire des projets durables.
                </h1>
                <p class="text-white/80 text-lg leading-relaxed mb-10">
                    Le Groupe SIBEA rassemble des compétences complémentaires
                    dans le BTP, l’immobilier, l’énergie et l’agro-industrie
                    pour accompagner des projets structurants et créateurs de valeur.
                </p>
                <div class="flex flex-wrap gap-4">
                    <flux:button href="{{ route('sectors.index') }}" variant="primary" class="bg-cuivre!">
                        Découvrir nos secteurs
                    </flux:button>
                    <flux:button href="{{ route('projects.index') }}" variant="outline"
                        class="bg-transparent! text-white! border-white/40! hover:bg-white/10!">
                        Voir nos réalisations
                    </flux:button>
                </div>
            </div>
            <div class="grid" aria-live="polite">
                <template x-for="(sector, index) in sectors" :key="'caption-' + index">
                    <div x-show="current === index" x-transition.opacity.duration.700ms
                        :aria-hidden="current !== index" class="col-start-1 row-start-1">
                        <div class="mb-4">
                            <span
                                class="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-[Manrope] font-semibold tracking-widest backdrop-blur">
                                <span class="inline-block w-2 h-2 rounded-full bg-cuivre"
                                    aria-hidden="true"></span>
                                GROUPE SIBEA — <span x-text="sector.name"></span>
                            </span>
                        </div>
                        <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl leading-tight mb-4 max-w-lg"
                            x-text="sector.title"></h2>
                        <p class="text-white/80 max-w-md mb-6" x-text="sector.description"></p>
                        <a :href="sector.url"
                            class="inline-flex items-center justify-center gap-2 rounded-lg bg-cuivre px-4 py-2 text-sm font-[Manrope] font-semibold text-white self-start">
                            <span x-text="sector.cta"></span> →
                        </a>
                    </div>
                </template>
            </div>
        </div>

        <button x-on:click="prev()" aria-label="Slide précédent"
            class="absolute left-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 text-white">‹</button>
        <button x-on:click="next()" aria-label="Slide suivant"
            class="absolute right-4 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/20 hover:bg-white/40 text-white">›</button>

        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex items-center gap-2 z-10" role="tablist">
            <template x-for="(sector, index) in sectors" :key="index">
                <button x-on:click="goTo(index)" :aria-selected="current === index"
                    :aria-label="`Aller au slide ${index + 1} : ${sector.name}`"
                    :class="current === index ? 'bg-cuivre w-8' : 'bg-white/40 w-2'"
                    class="h-2 rounded-full transition-all"></button>
            </template>
        </div>
        <div class="absolute bottom-6 right-10 lg:right-16 z-10 text-white/70 text-sm font-[Manrope] font-semibold"
            aria-hidden="true">
            <span x-text="String(current + 1).padStart(2, '0')"></span>
            <span class="text-white/40"> / </span>
            <span x-text="String(sectors.length).padStart(2, '0')"></span>
        </div>
    </section>

    @if($this->statistics->isNotEmpty())
        <section class="bg-nuit text-white border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-12 grid grid-cols-2 lg:grid-cols-4 gap-8">
                @foreach($this->statistics as $statistic)
                    <div wire:key="statistic-{{ $statistic->id }}" class="text-center">
                        <div class="font-[Manrope] font-extrabold text-4xl lg:text-5xl text-cuivre">{{ $statistic->value }}</div>
                        <div class="text-sm text-white/70 mt-1">{{ $statistic->label }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="bg-white border-b border-bordure">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-8">
            <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-8">
                <div>
                    <div class="font-[Manrope] font-bold text-lg mb-1">Qualité</div>
                    <p class="text-sm text-ardoise leading-relaxed">Des réalisations contrôlées, conformes aux
                        normes.</p>
                </div>
                <div>
                    <div class="font-[Manrope] font-bold text-lg mb-1">Fiabilité</div>
                    <p class="text-sm text-ardoise leading-relaxed">Des engagements tenus, délais et budgets
                        respectés.</p>
                </div>
                <div>
                    <div class="font-[Manrope] font-bold text-lg mb-1">Innovation</div>
                    <p class="text-sm text-ardoise leading-relaxed">Des solutions modernes, adaptées à chaque projet.
                    </p>
                </div>
                <div>
                    <div class="font-[Manrope] font-bold text-lg mb-1">Responsabilité</div>
                    <p class="text-sm text-ardoise leading-relaxed">Sécurité, conformité et conduite exemplaire.</p>
                </div>
                <div>
                    <div class="font-[Manrope] font-bold text-lg mb-1">Durabilité</div>
                    <p class="text-sm text-ardoise leading-relaxed">Des ouvrages pensés pour durer et créer de la
                        valeur.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-nuit text-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">NOS SECTEURS</div>
            <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl mb-4">Tout ce que vos projets exigent, au
                même endroit.</h2>
            <p class="text-white/70 text-lg  mb-8">Quatre secteurs d’activité, une même exigence de qualité
                et de durabilité.</p>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($this->sectors as $index => $sector)
                    <a wire:key="sector-{{ $sector->id }}" href="{{ route('sectors.show', $sector->slug) }}"
                        class="rounded-lg border border-white/15 bg-white/5 p-6 hover:border-cuivre hover:bg-white/10 transition-colors flex flex-col">
                        <div class="text-xs font-[Manrope] font-semibold tracking-widest text-cuivre mb-3">
                            0{{ $index + 1 }}</div>
                        <h3 class="font-[Manrope] font-bold text-xl mb-2">{{ $sector->name }}</h3>
                        <p class="text-sm text-white/70 leading-relaxed mb-4">{{ $sector->short_description }}</p>
                        <span class="mt-auto text-sm font-[Manrope] font-semibold text-cuivre">
                            {{ $sector->expertises_count }} expertise{{ $sector->expertises_count > 1 ? 's' : '' }} →
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section>
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">NOS EXPERTISES</div>
            <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl mb-4">Cinq savoir-faire complémentaires.</h2>
            <p class="text-ardoise text-lg mb-8">De l’étude à la réalisation, chaque expertise s’appuie
                sur des prestations concrètes.</p>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($this->expertises as $expertise)
                    <a wire:key="expertise-{{ $expertise->id }}"
                        href="{{ route('expertises.show', $expertise->slug) }}"
                        class="border border-bordure rounded-lg p-6 bg-white hover:border-cuivre hover:shadow-lg transition-all flex flex-col">
                        <h3 class="font-[Manrope] font-bold text-xl mb-2">{{ $expertise->name }}</h3>
                        <p class="text-sm text-ardoise mb-4">{{ $expertise->short_description }}</p>
                        @if ($expertise->services->isNotEmpty())
                            <ul class="text-sm text-anthracite space-y-1.5 mb-4">
                                @foreach ($expertise->services->take(4) as $service)
                                    <li class="flex gap-2"><span class="text-cuivre">•</span>{{ $service->name }}
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                        <span class="mt-auto text-sm font-[Manrope] font-semibold text-cuivre">Explorer l’expertise
                            →</span>
                    </a>
                @endforeach
                <div class="rounded-lg bg-nuit text-white p-6 flex flex-col justify-center">
                    <h3 class="font-[Manrope] font-bold text-xl mb-2">Un besoin spécifique ?</h3>
                    <p class="text-sm text-white/70 mb-4">Parlons de votre projet avec l’équipe SIBEA.</p>
                    <flux:button variant="primary" class="!bg-cuivre self-start"
                        x-on:click="$flux.modal('contact').open()">
                        Nous contacter
                    </flux:button>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-white border-y border-bordure">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
            <div class="flex flex-wrap items-end justify-between gap-4 mb-12">
                <div>
                    <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">RÉALISATIONS
                    </div>
                    <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl">Nos projets récents.</h2>
                </div>
                <a href="{{ route('projects.index') }}"
                    class="text-sm font-[Manrope] font-semibold text-cuivre">Voir toutes les réalisations →</a>
            </div>
            @if ($this->projects->isNotEmpty())
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($this->projects as $project)
                        <a wire:key="project-{{ $project->id }}"
                            href="{{ route('projects.show', $project->slug) }}"
                            class="border border-bordure rounded-lg p-6 bg-white hover:border-cuivre hover:shadow-lg transition-all flex flex-col">
                            @if ($project->sectors->isNotEmpty())
                                <div class="text-xs font-[Manrope] font-semibold tracking-widest text-cuivre mb-2">
                                    {{ $project->sectors->first()->name }}</div>
                            @endif
                            <h3 class="font-[Manrope] font-bold text-xl mb-2">{{ $project->title }}</h3>
                            <p class="text-sm text-ardoise mb-4">{{ $project->short_description }}</p>
                            <span class="mt-auto text-sm font-[Manrope] font-semibold">Lire l’étude de cas →</span>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="text-ardoise">Nos premières réalisations seront publiées prochainement.</p>
            @endif
        </div>
    </section>

    <section class="bg-nuit text-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">NOTRE MÉTHODE</div>
            <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl mb-12 max-w-3xl">Comment nous planifions,
                réalisons et suivons chaque projet.</h2>
            <div class="grid md:grid-cols-3 gap-10 mb-12">
                <div>
                    <div class="font-[Manrope] font-extrabold text-4xl text-cuivre mb-4">01</div>
                    <h3 class="font-[Manrope] font-bold text-xl mb-2">Écoute & étude</h3>
                    <p class="text-sm text-white/70 leading-relaxed">Nous cadrons votre besoin, étudions la faisabilité
                        et chiffrons en toute transparence.</p>
                </div>
                <div>
                    <div class="font-[Manrope] font-extrabold text-4xl text-cuivre mb-4">02</div>
                    <h3 class="font-[Manrope] font-bold text-xl mb-2">Réalisation pilotée</h3>
                    <p class="text-sm text-white/70 leading-relaxed">Des équipes qualifiées, un suivi rigoureux et une
                        communication claire à chaque étape.</p>
                </div>
                <div>
                    <div class="font-[Manrope] font-extrabold text-4xl text-cuivre mb-4">03</div>
                    <h3 class="font-[Manrope] font-bold text-xl mb-2">Suivi & durabilité</h3>
                    <p class="text-sm text-white/70 leading-relaxed">Réception contrôlée, accompagnement et ouvrages
                        pensés pour durer.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-4">
                <a href="{{ route('expertises.index') }}"
                    class="text-sm font-[Manrope] font-semibold text-cuivre">Découvrir nos expertises →</a>
            </div>
        </div>
    </section>

    @if ($this->testimonials->isNotEmpty())
        <section class="bg-casse">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
                <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">TÉMOIGNAGES</div>
                <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl mb-12">Ce que disent nos clients.</h2>
                <div class="grid md:grid-cols-3 gap-6">
                    @foreach ($this->testimonials as $testimonial)
                        <figure wire:key="testimonial-{{ $testimonial->id }}"
                            class="border border-bordure rounded-lg p-6 bg-white flex flex-col">
                            <div class="text-cuivre tracking-widest mb-3" aria-label="Note : 5 sur 5">★★★★★</div>
                            <blockquote class="text-sm text-anthracite leading-relaxed mb-6">«
                                {{ $testimonial->content }} »</blockquote>
                            <figcaption class="mt-auto flex items-center gap-3">
                                <span
                                    class="inline-flex items-center justify-center w-11 h-11 rounded-full bg-nuit text-white font-[Manrope] font-bold"
                                    aria-hidden="true">
                                    {{ mb_strtoupper(mb_substr($testimonial->author_name, 0, 1)) }}
                                </span>
                                <span>
                                    <span
                                        class="block font-[Manrope] font-semibold text-sm">{{ $testimonial->author_name }}</span>
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

    <section class="bg-white border-y border-bordure">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-24 text-center">
            <h2 class="font-[Manrope] font-extrabold text-3xl lg:text-5xl mb-4">Vous avez un projet à structurer ?</h2>
            <p class="text-ardoise text-lg max-w-2xl mx-auto mb-10">Obtenez un devis gratuit et sans engagement.
                Notre équipe vous répond rapidement.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <flux:button variant="primary" class="!bg-cuivre" x-on:click="$flux.modal('contact').open()">
                    Nous contacter
                </flux:button>
                <flux:button href="{{ route('projects.index') }}" variant="outline">
                    Voir nos réalisations
                </flux:button>
            </div>
        </div>
    </section>
</div>
