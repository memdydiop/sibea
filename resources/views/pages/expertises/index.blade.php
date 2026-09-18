<?php

use App\Models\Expertise;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    #[Computed]
    public function expertises()
    {
        return Expertise::active()
            ->ordered()
            ->with([
                'sectors',
                'services' => fn ($query) => $query->active()->ordered(),
            ])
            ->get();
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        <img src="{{ asset('images/heroes/expertises.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-12">
            <div class="text-cuivre font-display font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-display font-extrabold tracking-tight text-4xl mb-6">Nos expertises</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">{{ $this->expertises->count() }} savoir-faire complémentaires au service de vos projets.</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-32">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach($this->expertises as $expertise)
                @php($position = str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT))
                @php($cover = $expertise->getFirstMediaUrl('cover', 'thumb'))
                @php($benefits = collect($expertise->benefits)->filter()->values())
                @php($steps = collect($expertise->process_steps)->filter()->values())
                @php($remainingBenefits = $benefits->count() - 6)
                @php($remainingSteps = $steps->count() - 5)
                @php($remainingServices = $expertise->services->count() - 6)
                <article
                    wire:key="expertise-{{ $expertise->id }}"
                    class="reveal-up group flex flex-col overflow-hidden rounded-lg border border-bordure bg-surface transition duration-300 ease-out focus-within:border-cuivre hover:border-cuivre hover:shadow-md"
                >
                    <div class="relative h-56 overflow-hidden bg-nuit lg:h-64">
                        @if($cover)
                            <img src="{{ $cover }}" alt="" loading="lazy" decoding="async" class="h-full w-full object-cover transition-transform duration-500 ease-[cubic-bezier(0.23,1,0.32,1)] motion-safe:group-hover:scale-[1.03]">
                        @else
                            <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(rgba(255,255,255,.6) 1px, transparent 1px); background-size: 22px 22px;" aria-hidden="true"></div>
                            <span class="pointer-events-none absolute -bottom-12 -right-3 font-display text-[11rem] font-extrabold leading-none text-white/5" aria-hidden="true">{{ mb_substr($expertise->name, 0, 1) }}</span>
                        @endif
                        <div class="absolute inset-0 bg-linear-to-t from-nuit via-nuit/45 to-nuit/10" aria-hidden="true"></div>

                        <div class="absolute inset-x-5 top-5 flex items-start justify-between gap-4">
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($expertise->sectors->take(2) as $sector)
                                    <a wire:key="expertise-{{ $expertise->id }}-sector-{{ $sector->id }}" href="{{ route('sectors.index') }}" class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm transition-colors duration-300 hover:border-cuivre hover:text-cuivre focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre">{{ $sector->name }}</a>
                                @endforeach
                            </div>
                            <span class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col p-6 lg:p-8">
                        <h2 class="font-display text-2xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">{{ $expertise->name }}</h2>

                        @if($expertise->short_description ?? $expertise->description)
                            <p class="mt-3 text-sm leading-relaxed text-ardoise">{{ $expertise->short_description ?? $expertise->description }}</p>
                        @endif

                        @if($benefits->isNotEmpty())
                            <div class="mt-6 border-t border-bordure pt-5">
                                <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Bénéfices</div>
                                <ul class="mt-3 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                                    @foreach($benefits->take(6) as $index => $benefit)
                                        <li wire:key="expertise-{{ $expertise->id }}-benefit-{{ $index }}" class="flex items-start gap-2.5 text-sm text-anthracite">
                                            <svg class="mt-0.5 size-4 shrink-0 text-cuivre" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                                            </svg>
                                            <span>{{ $benefit }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                                @if($remainingBenefits > 0)
                                    <div class="mt-3 text-xs text-ardoise">+ {{ $remainingBenefits }} autre{{ $remainingBenefits > 1 ? 's' : '' }} bénéfice{{ $remainingBenefits > 1 ? 's' : '' }}</div>
                                @endif
                            </div>
                        @endif

                        @if($steps->isNotEmpty())
                            <div class="mt-6 border-t border-bordure pt-5">
                                <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Étapes d’intervention</div>
                                <ol class="mt-3 space-y-2.5">
                                    @foreach($steps->take(5) as $index => $step)
                                        <li wire:key="expertise-{{ $expertise->id }}-step-{{ $index }}" class="flex items-start gap-3 text-sm text-anthracite">
                                            <span class="font-display text-lg font-extrabold leading-none text-cuivre">{{ sprintf('%02d', $index + 1) }}</span>
                                            <span class="pt-0.5">{{ $step }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                                @if($remainingSteps > 0)
                                    <div class="mt-3 text-xs text-ardoise">+ {{ $remainingSteps }} autre{{ $remainingSteps > 1 ? 's' : '' }} étape{{ $remainingSteps > 1 ? 's' : '' }}</div>
                                @endif
                            </div>
                        @endif

                        @if($expertise->services->isNotEmpty())
                            <div class="mt-6 border-t border-bordure pt-5">
                                <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Prestations associées</div>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    @foreach($expertise->services->take(6) as $service)
                                        <span wire:key="expertise-{{ $expertise->id }}-service-{{ $service->id }}" class="rounded-full border border-bordure bg-casse px-3 py-1 text-xs font-medium text-anthracite">{{ $service->name }}</span>
                                    @endforeach
                                    @if($remainingServices > 0)
                                        <span class="rounded-full border border-dashed border-bordure px-3 py-1 text-xs text-ardoise">+ {{ $remainingServices }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="mt-auto flex justify-end pt-6">
                            <a
                                href="{{ route('contact') }}"
                                class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 hover:bg-cuivre hover:text-nuit focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre focus-visible:ring-offset-2"
                            >
                                Nous contacter
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>
                    </div>
                </article>
            @endforeach

            <div class="on-dark reveal-up relative flex flex-col overflow-hidden rounded-lg
             bg-nuit p-6 text-white ring-1 ring-white/10 lg:p-8">
                <div class="pointer-events-none absolute -left-24 -top-24 size-72 rounded-full bg-cuivre/10 blur-3xl" aria-hidden="true"></div>
                <div class="relative">
                    <div class="font-display text-xs font-semibold uppercase tracking-[0.22em] text-cuivre">Besoin spécifique</div>
                    <h2 class="mt-3 font-display text-2xl font-extrabold tracking-tight">Un besoin qui sort du cadre ?</h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/70">Décrivez votre projet : l’équipe SIBEA vous oriente vers la bonne expertise et les bonnes prestations.</p>
                </div>
                <div class="relative mt-auto pt-8">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-full bg-cuivre px-5 py-2.5 font-display text-sm font-semibold text-nuit transition-colors duration-300 hover:bg-white">
                        Nous contacter
                        <span aria-hidden="true">→</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</div>
