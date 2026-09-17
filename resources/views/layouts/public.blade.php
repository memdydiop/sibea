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
    <link rel="icon" type="image/jpeg" href="{{ asset('images/logo-sibea.jpeg') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=manrope:400,500,600,700,800|inter:400,500,600" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-casse text-anthracite font-[Inter] antialiased">

    <header
        x-data="{ open: false, scrolled: false }"
        x-on:scroll.window="scrolled = window.scrollY > 40"
        class="fixed top-0 inset-x-0 z-50 bg-white text-anthracite shadow-lg border-b-6 border-cuivre"
    >
        <div class="max-w-7xl mx-auto px-6 lg:px-8 flex items-center justify-between transition-all duration-300" :class="scrolled ? 'h-20' : 'h-30'">
            <a href="{{ route('home') }}" class="flex flex-col leading-none" aria-label="Groupe SIBEA — Accueil">
                <img src="{{ asset('images/logo-sibea.jpeg') }}" alt="Groupe SIBEA" class="w-auto mix-blend-multiply transition-all duration-300" :class="scrolled ? 'h-16' : 'h-28'">
            </a>
            <nav class="hidden lg:flex items-center gap-8 text-sm font-[Manrope] font-medium">
                <a href="{{ route('sectors.index') }}" class="hover:text-cuivre text-lg {{ request()->routeIs('sectors.*') ? 'text-cuivre' : '' }}">Secteurs d’activité</a>
                <a href="{{ route('expertises.index') }}" class="hover:text-cuivre text-lg {{ request()->routeIs('expertises.*') ? 'text-cuivre' : '' }}">Expertises</a>
                <a href="{{ route('projects.index') }}" class="hover:text-cuivre text-lg {{ request()->routeIs('projects.*') ? 'text-cuivre' : '' }}">Réalisations</a>
                <a href="{{ route('contact') }}" class="hover:text-cuivre text-lg {{ request()->routeIs('contact') ? 'text-cuivre' : '' }}">Contact</a>
            </nav>
            <div class="flex items-center gap-3">
                <flux:button variant="ghost" href="{{ route('login') }}" class="hidden sm:inline-flex">
                    Se connecter
                </flux:button>
                <flux:button variant="primary" x-on:click="$flux.modal('contact').open()" class="!bg-cuivre !text-white hidden sm:inline-flex">
                    Nous contacter
                </flux:button>
                <button
                    x-on:click="open = !open"
                    :aria-expanded="open"
                    aria-label="Menu"
                    class="lg:hidden inline-flex items-center justify-center w-11 h-11 rounded-lg border border-bordure text-nuit"
                >
                    <span x-show="!open" class="text-xl leading-none">☰</span>
                    <span x-show="open" class="text-xl leading-none">✕</span>
                </button>
            </div>
        </div>
        <nav x-show="open" x-cloak class="lg:hidden bg-white border-t border-bordure" aria-label="Menu mobile">
            <div class="px-6 py-4 flex flex-col gap-1 text-sm font-[Manrope] font-medium">
                <a x-on:click="open = false" href="{{ route('sectors.index') }}" class="py-3 border-b border-bordure">Secteurs d’activité</a>
                <a x-on:click="open = false" href="{{ route('expertises.index') }}" class="py-3 border-b border-bordure">Expertises</a>
                <a x-on:click="open = false" href="{{ route('projects.index') }}" class="py-3 border-b border-bordure">Réalisations</a>
                <a x-on:click="open = false" href="{{ route('contact') }}" class="py-3 border-b border-bordure">Contact</a>
                <a x-on:click="open = false" href="{{ route('login') }}" class="py-3 border-b border-bordure">Se connecter</a>
                <flux:button variant="primary" x-on:click="open = false; $flux.modal('contact').open()" class="!bg-cuivre !text-white mt-3">
                    Nous contacter
                </flux:button>
            </div>
        </nav>
    </header>

    <main id="main" x-data="{ scrolled: window.scrollY > 40 }" x-on:scroll.window="scrolled = window.scrollY > 40" :class="scrolled ? 'pt-20' : 'pt-30'" class="transition-all duration-300">{{ $slot }}</main>

    <footer class="bg-nuit text-white">
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 grid grid-cols-1 md:grid-cols-4 gap-12">
            <div>
                <span class="inline-block bg-white rounded-lg px-3 py-2 mb-4">
                    <img src="{{ asset('images/logo-sibea.jpeg') }}" alt="Groupe SIBEA" class="h-10 w-auto">
                </span>
                <p class="text-sm text-white/70 leading-relaxed">
                    Un groupe multi-activités intervenant dans le BTP, l’immobilier,
                    l’énergie et l’agro-industrie.
                </p>
            </div>
            <div>
                <div class="font-[Manrope] font-semibold mb-4">Secteurs</div>
                <ul class="space-y-2 text-sm text-white/70">
                    @foreach(\App\Models\Sector::cachedActiveList() as $sector)
                        <li>
                            <a href="{{ route('sectors.show', $sector->slug) }}">{{ $sector->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <div class="font-[Manrope] font-semibold mb-4">Expertises</div>
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('expertises.index') }}">Nos expertises</a></li>
                    <li><a href="{{ route('projects.index') }}">Réalisations</a></li>
                </ul>
            </div>
            <div>
                <div class="font-[Manrope] font-semibold mb-4">Contact</div>
                <ul class="space-y-2 text-sm text-white/70">
                    <li><a href="{{ route('contact') }}">Nous contacter</a></li>
                    <li><a href="{{ route('legal') }}">Mentions légales</a></li>
                    <li><a href="{{ route('privacy') }}">Politique de confidentialité</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10">
            <div class="max-w-7xl mx-auto px-6 lg:px-8 py-6 text-xs text-white/50">
                © {{ date('Y') }} Groupe SIBEA. Tous droits réservés.
            </div>
        </div>
    </footer>

    <livewire:contact-modal />
    @fluxScripts
    @livewireScripts
</body>
</html>
