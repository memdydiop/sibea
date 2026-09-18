<?php

use App\Models\Expertise;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public bool $showDetails = false;

    public ?int $selectedExpertiseId = null;

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

    #[Computed]
    public function selectedExpertise(): ?Expertise
    {
        if ($this->selectedExpertiseId === null) {
            return null;
        }

        return Expertise::with([
            'sectors',
            'services' => fn ($query) => $query->active()->ordered(),
        ])->find($this->selectedExpertiseId);
    }

    public function openDetails(int $id): void
    {
        $this->selectedExpertiseId = $id;
        $this->showDetails = true;
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        <img src="{{ setting_media_url('visuals.hero.expertises') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-14">
            <nav aria-label="Fil d’Ariane" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/60">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-cuivre" wire:navigate>Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-white/90" aria-current="page">{{ setting('expertises.hero.title') }}</li>
                </ol>
            </nav>
            <h1 class="font-display font-extrabold text-4xl lg:text-6xl mb-6 max-w-4xl">{{ setting('expertises.hero.title') }}</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">{{ str_replace(':count', (string) $this->expertises->count(), setting('expertises.hero.subtitle')) }}</p>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-6 py-20 lg:px-8 lg:py-32">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            @foreach($this->expertises as $expertise)
                @php($position = str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT))
                @php($cover = $expertise->getFirstMediaUrl('cover', 'thumb'))
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
                                    <a wire:key="expertise-{{ $expertise->id }}-sector-{{ $sector->id }}" href="{{ route('sectors.show', $sector->slug) }}" class="rounded-full border border-white/15 bg-white/10 px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-white/85 backdrop-blur-sm transition-colors duration-300 hover:border-cuivre hover:text-cuivre focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre" wire:navigate>{{ $sector->name }}</a>
                                @endforeach
                            </div>
                            <span class="font-display text-[11px] font-bold tracking-[0.22em] text-white/50">{{ $position }}</span>
                        </div>
                    </div>

                    <div class="flex flex-1 flex-col p-6 lg:p-8">
                        <h2 class="font-display text-xl font-extrabold text-nuit leading-snug tracking-tight transition-colors duration-300 group-hover:text-cuivre">{{ $expertise->name }}</h2>

                        @if($expertise->short_description ?? $expertise->description)
                            <p class="mt-3 text-sm leading-relaxed text-ardoise">{{ $expertise->short_description ?? $expertise->description }}</p>
                        @endif

                        @if($expertise->services->isNotEmpty())
                            <div class="mt-5 flex flex-wrap gap-2">
                                @foreach($expertise->services->take(6) as $service)
                                    <span wire:key="expertise-{{ $expertise->id }}-service-{{ $service->id }}" class="rounded-full border border-bordure bg-casse px-3 py-1 text-xs font-medium text-anthracite">{{ $service->name }}</span>
                                @endforeach
                                @if($remainingServices > 0)
                                    <span class="rounded-full border border-dashed border-bordure px-3 py-1 text-xs text-ardoise">+ {{ $remainingServices }}</span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-auto flex flex-wrap items-center gap-2 pt-6">
                            <button
                                type="button"
                                wire:click="openDetails({{ $expertise->id }})"
                                class="inline-flex items-center gap-2 rounded-full bg-nuit px-4 py-2 font-display text-sm font-semibold text-white transition-colors duration-300 hover:bg-cuivre hover:text-nuit focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre focus-visible:ring-offset-2"
                            >
                                Bénéfices &amp; étapes
                                <span aria-hidden="true">→</span>
                            </button>
                            <a
                                href="{{ route('contact') }}"
                                class="inline-flex items-center gap-2 rounded-full border border-bordure px-4 py-2 font-display text-sm font-semibold text-anthracite transition-colors duration-300 hover:border-cuivre hover:text-cuivre focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre focus-visible:ring-offset-2"
                                wire:navigate>
                                Nous contacter
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
                    <h2 class="mt-3 font-display text-2xl font-extrabold tracking-tight">{{ setting('expertises.cta.title') }}</h2>
                    <p class="mt-3 text-sm leading-relaxed text-white/70">{{ setting('expertises.cta.subtitle') }}</p>
                </div>
                <div class="relative mt-auto flex flex-wrap items-center gap-3 pt-8">
                    <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 rounded-full bg-cuivre px-5 py-2.5 font-display text-sm font-semibold text-nuit transition-colors duration-300 hover:bg-white" wire:navigate>
                        Nous contacter
                        <span aria-hidden="true">→</span>
                    </a>
                    @if(setting('contact.whatsapp'))
                        <a href="https://wa.me/{{ setting('contact.whatsapp') }}?text={{ urlencode(setting('contact.whatsapp_message')) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center gap-2 rounded-full border border-white/25 px-4 py-2.5 font-display text-sm font-semibold text-white/90 transition-colors duration-300 hover:border-[#25D366] hover:text-[#25D366]">
                            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                            {{ setting('home.expertise_cta.whatsapp_label') }}
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <flux:modal wire:model="showDetails" class="md:w-[46rem]">
        @if($this->selectedExpertise)
            @php($benefits = collect($this->selectedExpertise->benefits)->filter()->values())
            @php($steps = collect($this->selectedExpertise->process_steps)->filter()->values())

            <div class="space-y-6">
                <div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach($this->selectedExpertise->sectors as $sector)
                            <a href="{{ route('sectors.show', $sector->slug) }}" class="rounded-full border border-bordure bg-casse px-2.5 py-1 font-display text-[10px] font-semibold uppercase tracking-wider text-ardoise transition-colors duration-300 hover:border-cuivre hover:text-cuivre" wire:navigate>{{ $sector->name }}</a>
                        @endforeach
                    </div>
                    <flux:heading size="lg" class="mt-4">{{ $this->selectedExpertise->name }}</flux:heading>
                    @if($this->selectedExpertise->description)
                        <p class="mt-2 text-sm leading-relaxed text-ardoise">{{ $this->selectedExpertise->description }}</p>
                    @endif
                </div>

                @if($benefits->isNotEmpty())
                    <div class="border-t border-bordure pt-5">
                        <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Bénéfices</div>
                        <ul class="mt-3 grid gap-x-6 gap-y-2.5 sm:grid-cols-2">
                            @foreach($benefits as $index => $benefit)
                                <li wire:key="detail-benefit-{{ $index }}" class="flex items-start gap-2.5 text-sm text-anthracite">
                                    <svg class="mt-0.5 size-4 shrink-0 text-cuivre" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
                                    </svg>
                                    <span>{{ $benefit }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($steps->isNotEmpty())
                    <div class="border-t border-bordure pt-5">
                        <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Étapes d’intervention</div>
                        <ol class="mt-3 space-y-2.5">
                            @foreach($steps as $index => $step)
                                <li wire:key="detail-step-{{ $index }}" class="flex items-start gap-3 text-sm text-anthracite">
                                    <span class="font-display text-lg font-extrabold leading-none text-cuivre">{{ sprintf('%02d', $index + 1) }}</span>
                                    <span class="pt-0.5">{{ $step }}</span>
                                </li>
                            @endforeach
                        </ol>
                    </div>
                @endif

                @if($this->selectedExpertise->services->isNotEmpty())
                    <div class="border-t border-bordure pt-5">
                        <div class="font-display text-[10px] font-semibold uppercase tracking-[0.18em] text-ardoise/70">Prestations associées</div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach($this->selectedExpertise->services as $service)
                                <span wire:key="detail-service-{{ $service->id }}" class="rounded-full border border-bordure bg-casse px-3 py-1 text-xs font-medium text-anthracite">{{ $service->name }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-2 border-t border-bordure pt-5">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="$set('showDetails', false)">Fermer</flux:button>
                    <flux:button variant="primary" :href="route('contact')" wire:navigate>Nous contacter</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
