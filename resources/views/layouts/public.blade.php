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
                <flux:button variant="ghost" href="{{ route('login') }}" target="_blank" rel="noopener" class="hidden sm:inline-flex text-white! hover:text-cuivre!">
                    {{ setting('header.login_label') }}
                </flux:button>
                <button
                    x-on:click="open = !open"
                    :aria-expanded="open"
                    aria-label="Menu"
                    class="lg:hidden inline-flex h-11 w-11 items-center justify-center rounded-lg border border-bordure text-anthracite transition-colors hover:border-cuivre hover:text-cuivre"
                >
                    <span x-show="!open" class="text-xl leading-none">☰</span>
                    <span x-show="open" class="text-xl leading-none">✕</span>
                </button>
            </div>
        </div>
        <nav x-show="open" x-cloak class="lg:hidden border-t border-bordure bg-casse" aria-label="Menu mobile">
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
                <a x-on:click="open = false" href="{{ route('login') }}" target="_blank" rel="noopener" class="py-3 border-b border-bordure text-anthracite/85 hover:text-cuivre">{{ setting('header.login_label') }}</a>
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
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('contact') }}" class="transition-colors hover:text-cuivre" wire:navigate>Nous contacter</a></li>
                    <li><a href="{{ route('legal') }}" class="transition-colors hover:text-cuivre" wire:navigate>Mentions légales</a></li>
                    <li><a href="{{ route('privacy') }}" class="transition-colors hover:text-cuivre" wire:navigate>Politique de confidentialité</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6 text-xs text-white/50">
                © {{ date('Y') }} {{ setting('general.footer_copyright') }}
            </div>
        </div>
    </footer>

    @fluxScripts
    @livewireScripts
</body>
</html>
