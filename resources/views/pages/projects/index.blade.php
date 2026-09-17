<?php

use App\Models\Project;
use App\Models\Sector;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    #[Url]
    public ?int $sector_id = null;

    public string $search = '';

    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->get();
    }

    #[Computed]
    public function projects()
    {
        return Project::published()
            ->when($this->sector_id, fn ($query) => $query->whereHas('sectors', fn ($q) => $q->whereKey($this->sector_id)))
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(title) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->latest()
            ->paginate(9);
    }
};
?>

<div>
    <section class="relative bg-nuit text-white py-24 overflow-hidden">
        <img src="{{ asset('images/heroes/realisations.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-[Manrope] font-extrabold text-4xl lg:text-6xl mb-6">Réalisations</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">Nos projets réalisés, filtrez par secteur.</p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">

    <div class="flex flex-wrap gap-3 mb-10">
        <flux:button :variant="$sector_id === null ? 'primary' : 'outline'" wire:click="$set('sector_id', null)">Tous</flux:button>
        @foreach($this->sectors as $sector)
            <flux:button :variant="$sector_id === $sector->id ? 'primary' : 'outline'" wire:click="$set('sector_id', {{ $sector->id }})">
                {{ $sector->name }}
            </flux:button>
        @endforeach
    </div>

    <div class="grid md:grid-cols-3 gap-6">
        @foreach($this->projects as $project)
            <a wire:key="project-{{ $project->id }}" href="{{ route('projects.show', $project->slug) }}" class="border border-bordure rounded-lg p-6 bg-white hover:border-cuivre transition-colors">
                <h2 class="font-[Manrope] font-bold text-xl mb-2">{{ $project->title }}</h2>
                <p class="text-sm text-ardoise">{{ $project->short_description }}</p>
            </a>
        @endforeach
    </div>

    <div class="mt-10">
        <flux:pagination :paginator="$this->projects" />
    </div>
    </section>
</div>
