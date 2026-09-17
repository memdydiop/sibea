<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {};
?>

<div>
    <section class="relative bg-nuit text-white py-24 overflow-hidden">
        <img src="{{ asset('images/heroes/contact.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-[Manrope] font-semibold tracking-widest text-sm mb-4">GROUPE SIBEA</div>
            <h1 class="font-[Manrope] font-extrabold text-4xl lg:text-6xl mb-6">Contact</h1>
            <p class="text-white/80 text-lg max-w-2xl leading-relaxed">Parlons de votre projet. Notre équipe vous répond rapidement.</p>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-6 lg:px-8 py-24">
    <p class="text-ardoise text-lg max-w-2xl mb-10">
        Cliquez sur le bouton ci-dessous pour ouvrir le formulaire de contact.
    </p>

    <flux:button variant="primary" class="!bg-cuivre" x-on:click="$flux.modal('contact').open()">
        Nous contacter
    </flux:button>

    <noscript>
        <div class="mt-10 border border-bordure rounded-lg p-6 bg-white max-w-xl">
            <p class="text-sm text-anthracite">
                JavaScript est désactivé. Écrivez-nous directement à
                <a class="text-cuivre underline" href="mailto:contact@sibea.ci">contact@sibea.ci</a>.
            </p>
        </div>
    </noscript>
    </section>
</div>
