<?php

use App\Models\Sector;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Sector $sector;

    public function mount(): void
    {
        abort_if(! $this->sector->is_active, 404);
    }

    #[Computed]
    public function expertises()
    {
        return $this->sector->expertises()->where('is_active', true)->ordered()->get();
    }

    #[Computed]
    public function services()
    {
        return $this->sector->expertises()
            ->with('services')
            ->get()
            ->flatMap->services
            ->where('is_active', true)
            ->unique('id');
    }

    #[Computed]
    public function projects()
    {
        return $this->sector->projects()->where('is_published', true)->latest()->take(6)->get();
    }
};
?>

<div>
    <section class="relative bg-nuit text-white py-32 overflow-hidden">
        @if($sector->getFirstMediaUrl('hero'))
            <img src="{{ $sector->getFirstMediaUrl('hero') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
            <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        @endif
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">
                SECTEUR D’ACTIVITÉ
            </div>
            <h1 class="font-[Manrope] font-extrabold text-5xl lg:text-6xl mb-6">
                {{ $sector->name }}
            </h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                {{ $sector->short_description }}
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
        <h2 class="font-[Manrope] font-extrabold text-3xl mb-8">Expertises associées</h2>
        <div class="grid md:grid-cols-2 gap-6 mb-16">
            @foreach($this->expertises as $expertise)
                <a href="{{ route('expertises.show', $expertise->slug) }}" class="border border-bordure rounded-lg p-6 bg-white hover:border-cuivre transition-colors">
                    <h3 class="font-[Manrope] font-bold text-xl">{{ $expertise->name }}</h3>
                    <p class="text-sm text-ardoise mt-2">{{ $expertise->short_description }}</p>
                </a>
            @endforeach
        </div>

        <h2 class="font-[Manrope] font-extrabold text-3xl mb-8">Réalisations</h2>
        <div class="grid md:grid-cols-3 gap-6 mb-16">
            @foreach($this->projects as $project)
                <a href="{{ route('projects.show', $project->slug) }}" class="border border-bordure rounded-lg p-6 bg-white hover:border-cuivre transition-colors">
                    <h3 class="font-[Manrope] font-bold text-lg">{{ $project->title }}</h3>
                    <p class="text-sm text-ardoise mt-2">{{ $project->short_description }}</p>
                </a>
            @endforeach
        </div>

        <div class="text-center">
            <flux:button variant="primary" class="!bg-cuivre" x-on:click="$flux.modal('contact').open()">
                Nous contacter
            </flux:button>
        </div>
    </section>
</div>
