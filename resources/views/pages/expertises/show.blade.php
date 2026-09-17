<?php

use App\Models\Expertise;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public Expertise $expertise;

    public function mount(): void
    {
        abort_if(! $this->expertise->is_active, 404);
    }

    #[Computed]
    public function projects()
    {
        return $this->expertise->projects()->where('is_published', true)->latest()->take(6)->get();
    }

    #[Computed]
    public function services()
    {
        return $this->expertise->services()->where('is_active', true)->ordered()->get();
    }
};
?>

<div>
    <section class="bg-nuit text-white py-32">
        <div class="max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">
                EXPERTISE
            </div>
            <h1 class="font-[Manrope] font-extrabold text-5xl lg:text-6xl mb-6">
                {{ $expertise->name }}
            </h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">
                {{ $expertise->short_description }}
            </p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
        <div class="prose max-w-2xl mb-16">
            <p>{{ $expertise->description }}</p>
        </div>

        <h2 class="font-[Manrope] font-extrabold text-3xl mb-8">Services associés</h2>
        <div class="grid md:grid-cols-2 gap-6 mb-16">
            @foreach($this->services as $service)
                <div class="border border-bordure rounded-lg p-6 bg-white">
                    <h3 class="font-[Manrope] font-bold text-lg">{{ $service->name }}</h3>
                    <p class="text-sm text-ardoise mt-2">{{ $service->short_description }}</p>
                </div>
            @endforeach
        </div>

        <h2 class="font-[Manrope] font-extrabold text-3xl mb-8">Réalisations liées</h2>
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
