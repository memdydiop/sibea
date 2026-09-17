<?php

use App\Models\Sector;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->get();
    }
};
?>

<div>
    <section class="relative bg-nuit text-white py-24 overflow-hidden">
        <img src="{{ asset('images/heroes/secteurs.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-[Manrope] font-extrabold text-4xl lg:text-6xl mb-6">Secteurs d’activité</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">Quatre secteurs, une même exigence de qualité et de durabilité.</p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
    <div class="grid md:grid-cols-2 gap-6">
        @foreach($this->sectors as $sector)
            <a href="{{ route('sectors.show', $sector->slug) }}" class="border border-bordure rounded-lg p-8 bg-white hover:border-cuivre transition-colors">
                <h2 class="font-[Manrope] font-bold text-2xl mb-3">{{ $sector->name }}</h2>
                <p class="text-ardoise mb-4">{{ $sector->short_description ?? $sector->description }}</p>
                <span class="text-cuivre font-[Manrope] font-semibold text-sm">Découvrir →</span>
            </a>
        @endforeach
    </div>
    </section>
</div>
