<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {};
?>

<div>
    <section class="on-dark relative bg-nuit text-white py-16 overflow-hidden">
        <img src="{{ asset('images/heroes/legal.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-display font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-display font-extrabold text-4xl lg:text-5xl">Mentions légales</h1>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-6 lg:px-8 py-16">
    <div class="prose max-w-none text-anthracite">
        <h2 class="font-display font-bold text-nuit text-2xl mt-8 mb-4">Éditeur du site</h2>
        <p>Groupe SIBEA — site vitrine institutionnel.</p>
        <h2 class="font-display font-bold text-nuit text-2xl mt-8 mb-4">Propriété intellectuelle</h2>
        <p>L’ensemble des contenus de ce site est protégé. Toute reproduction sans autorisation est interdite.</p>
        <h2 class="font-display font-bold text-nuit text-2xl mt-8 mb-4">Données personnelles</h2>
        <p>Voir notre <a class="underline text-cuivre" href="{{ route('privacy') }}">politique de confidentialité</a>.</p>
    </div>
    </section>
</div>
