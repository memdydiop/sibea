<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Groupe SIBEA — BTP, Immobilier, Énergie, Agro-industrie' }}</title>
    <meta name="description" content="{{ $description ?? 'Le Groupe SIBEA rassemble des expertises complémentaires dans le BTP, l’immobilier, l’énergie et l’agro-industrie.' }}">
    {{-- Partial SEO centralisé : exception documentée au principe "aucun composant réutilisable" --}}
    @include('partials.seo')
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=poppins:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="theme-sibea min-h-screen bg-casse text-anthracite font-[Poppins] antialiased selection:bg-cuivre selection:text-nuit">

    @php
        $headerLinks = [
            'sectors' => setting('header.link.sectors.visible') === '1',
            'expertises' => setting('header.link.expertises.visible') === '1',
            'projects' => setting('header.link.projects.visible') === '1',
            'contact' => setting('header.link.contact.visible') === '1',
        ];
    @endphp

    <header
        x-data="{ open: false, scrolled: false }"
        x-on:scroll.window="scrolled = window.scrollY > 40"
        class="fixed top-0 inset-x-0 z-50 shadow-md text-anthracite backdrop-blur-xl bg-nuit"
    >
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between transition-all duration-300 h-20">
            <a href="{{ route('home') }}"
                class="inline-flex items-center rounded-lg bg-surface px-3 py-2 transition-opacity duration-300 hover:opacity-90 focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-cuivre"
                aria-label="Groupe SIBEA — Accueil" wire:navigate>
                <img src="{{ setting_media_url('visuals.logo') }}" alt="Groupe SIBEA" class="h-10 w-auto lg:h-12">
            </a>
            <nav class="hidden lg:flex items-center gap-8 font-display text-base font-semibold">
                @if($headerLinks['sectors'])
                    <a href="{{ route('sectors.index') }}" class="text-white transition-colors hover:text-cuivre {{ request()->routeIs('sectors.*') ? 'text-cuivre!' : '' }}" wire:navigate>{{ setting('header.link.sectors.label') }}</a>
                @endif
                @if($headerLinks['expertises'])
                    <a href="{{ route('expertises.index') }}" class="text-white transition-colors hover:text-cuivre {{ request()->routeIs('expertises.*') ? 'text-cuivre!' : '' }}" wire:navigate>{{ setting('header.link.expertises.label') }}</a>
                @endif
                @if($headerLinks['projects'])
                    <a href="{{ route('projects.index') }}" class="text-white transition-colors hover:text-cuivre {{ request()->routeIs('projects.*') ? 'text-cuivre!' : '' }}" wire:navigate>{{ setting('header.link.projects.label') }}</a>
                @endif
                @if($headerLinks['contact'])
                    <a href="{{ route('contact') }}" class="text-white transition-colors hover:text-cuivre {{ request()->routeIs('contact') ? 'text-cuivre!' : '' }}" wire:navigate>{{ setting('header.link.contact.label') }}</a>
                @endif
            </nav>
            <div class="flex items-center gap-3">
                <button
                    x-on:click="open = !open"
                    :aria-expanded="open"
                    aria-label="Menu"
                    :aria-label="open ? 'Fermer le menu' : 'Ouvrir le menu'"
                    aria-controls="menu-mobile"
                    x-on:keydown.escape.window="open = false"
                    class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-lg border border-white/20 text-white transition-colors hover:border-cuivre hover:text-cuivre"
                >
                    <span x-show="!open" class="text-xl leading-none" aria-hidden="true">☰</span>
                    <span x-show="open" class="text-xl leading-none" aria-hidden="true">✕</span>
                </button>
            </div>
        </div>
        <nav x-show="open" x-cloak x-on:click.outside="open = false" id="menu-mobile" class="lg:hidden border-t border-bordure bg-casse" aria-label="Menu mobile">
            <div class="px-6 py-4 flex flex-col gap-1 font-display text-base font-semibold">
                @if($headerLinks['sectors'])
                    <a x-on:click="open = false" href="{{ route('sectors.index') }}" class="py-3 border-b border-bordure text-anthracite/85 hover:text-cuivre" wire:navigate>{{ setting('header.link.sectors.label') }}</a>
                @endif
                @if($headerLinks['expertises'])
                    <a x-on:click="open = false" href="{{ route('expertises.index') }}" class="py-3 border-b border-bordure text-anthracite/85 hover:text-cuivre" wire:navigate>{{ setting('header.link.expertises.label') }}</a>
                @endif
                @if($headerLinks['projects'])
                    <a x-on:click="open = false" href="{{ route('projects.index') }}" class="py-3 border-b border-bordure text-anthracite/85 hover:text-cuivre" wire:navigate>{{ setting('header.link.projects.label') }}</a>
                @endif
                @if($headerLinks['contact'])
                    <a x-on:click="open = false" href="{{ route('contact') }}" class="py-3 border-b border-bordure text-anthracite/85 hover:text-cuivre" wire:navigate>{{ setting('header.link.contact.label') }}</a>
                @endif
            </div>
        </nav>
    </header>

    <main id="main" x-data="{ scrolled: window.scrollY > 40 }" x-on:scroll.window="scrolled = window.scrollY > 40" class="transition-all duration-300 pt-20">{{ $slot }}</main>

    <footer class="on-dark border-t border-white/10 bg-nuit text-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-12">
            <div>
                <span class="mb-4 inline-flex rounded-lg bg-surface px-3 py-2">
                    <img src="{{ setting_media_url('visuals.logo') }}" alt="Groupe SIBEA" class="h-10 w-auto">
                </span>
                <p class="text-sm text-white/70 leading-relaxed">
                    {{ setting('general.footer_tagline') }}
                </p>
            </div>
            <div>
                <div class="font-display font-bold mb-4">Le groupe</div>
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('pages.show', ['page' => 'le-groupe']) }}" class="transition-colors hover:text-cuivre" wire:navigate>Qui sommes-nous</a></li>
                    <li><a href="{{ route('pages.show', ['page' => 'engagements']) }}" class="transition-colors hover:text-cuivre" wire:navigate>Engagements</a></li>
                </ul>
            </div>
            <div>
                <div class="font-display font-bold mb-4">Secteurs</div>
                <ul class="space-y-2 text-sm text-white/70">
                    @foreach(\App\Models\Sector::cachedActiveList() as $sector)
                        <li>
                            <a href="{{ route('sectors.show', $sector->slug) }}" class="transition-colors hover:text-cuivre" wire:navigate>{{ $sector->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="font-display font-bold mb-4">Expertises</div>
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('expertises.index') }}" class="transition-colors hover:text-cuivre" wire:navigate>Nos expertises</a></li>
                    <li><a href="{{ route('projects.index') }}" class="transition-colors hover:text-cuivre" wire:navigate>Réalisations</a></li>
                </ul>
            </div>
            <div>
                <div class="font-display font-bold mb-4">Contact</div>
                <ul class="space-y-3 text-sm text-white/70">
                    <li>
                        <a href="{{ route('contact') }}" class="transition-colors hover:text-cuivre" wire:navigate>Nous contacter</a>
                    </li>
                    @if(setting('contact.address'))
                        <li class="flex items-start gap-2">
                            <svg class="mt-0.5 h-4 w-4 shrink-0 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span class="leading-relaxed">{!! nl2br(e(setting('contact.address'))) !!}</span>
                        </li>
                    @endif
                    @if(setting('contact.phone'))
                        <li>
                            <a href="tel:{{ setting('contact.phone_link') }}" class="flex items-center gap-2 transition-colors hover:text-cuivre">
                                <svg class="h-4 w-4 shrink-0 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                {{ setting('contact.phone') }}
                            </a>
                        </li>
                    @endif
                    @if(setting('contact.email'))
                        <li>
                            <a href="mailto:{{ setting('contact.email') }}" class="flex items-center gap-2 transition-colors hover:text-cuivre">
                                <svg class="h-4 w-4 shrink-0 text-cuivre" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                {{ setting('contact.email') }}
                            </a>
                        </li>
                    @endif
                    <li><a href="{{ route('legal') }}" class="transition-colors hover:text-cuivre" wire:navigate>Mentions légales</a></li>
                    <li><a href="{{ route('privacy') }}" class="transition-colors hover:text-cuivre" wire:navigate>Politique de confidentialité</a></li>
                    @if(setting('social.facebook'))
                        <li class="pt-1">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-white/50">Suivez-nous</div>
                            <div class="flex items-center gap-2">
                                <a href="{{ setting('social.facebook') }}" target="_blank" rel="noopener" aria-label="SIBEA sur Facebook"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-cuivre hover:text-nuit">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                    </svg>
                                </a>
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-4 text-center text-xs text-white/50">
                Copyright © {{ date('Y') }} SIBEA. Conçu avec <span class="text-red-500" aria-hidden="true">♥</span> par <a href="mailto:mendydiop@gmail.com" class="font-bold text-cuivre transition-colors hover:underline">Save&amp;Dev</a>. Tous droits réservés.
            </div>
        </div>
    </footer>

    @fluxScripts
    @livewireScripts
</body>
</html>
