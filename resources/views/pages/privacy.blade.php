<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {};
?>

<div>
    <section class="relative bg-nuit text-white py-16 overflow-hidden">
        <img src="{{ asset('images/heroes/legal.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-[Manrope] font-extrabold text-4xl lg:text-5xl">Politique de confidentialité</h1>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-6 lg:px-8 py-16">
    <div class="prose max-w-none text-anthracite">
        <h2 class="font-[Manrope] font-bold text-2xl mt-8 mb-4">Données collectées</h2>
        <p>Les demandes envoyées via le formulaire de contact (nom, email, téléphone, message) sont conservées 3 ans après le dernier contact.</p>
        <h2 class="font-[Manrope] font-bold text-2xl mt-8 mb-4">Vos droits</h2>
        <p>Vous pouvez demander l’accès, la rectification ou la suppression de vos données en écrivant à <a class="underline text-cuivre" href="mailto:contact@sibea.ci">contact@sibea.ci</a>.</p>
        <h2 class="font-[Manrope] font-bold text-2xl mt-8 mb-4">Cookies</h2>
        <p>Ce site n’utilise que des cookies strictement nécessaires à son fonctionnement.</p>
    </div>
    </section>
</div>
