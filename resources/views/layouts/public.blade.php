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
        <div class="max-w-7xl mx-auto px-6 lg:px-8 py-16 grid grid-cols-1 md:grid-cols-5 2xl:grid-cols-7 gap-12">
            <div class="md:col-span-5 2xl:col-span-2">
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
                    <li><a href="{{ route('legal') }}" class="transition-colors hover:text-cuivre" wire:navigate>Mentions légales</a></li>
                    <li><a href="{{ route('privacy') }}" class="transition-colors hover:text-cuivre" wire:navigate>Politique de confidentialité</a></li>
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
            <div class="md:col-span-2 ">
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
                            <span class="leading-relaxed">{{ setting('contact.address') }}</span>
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
                    @php($followWhatsapp = setting('social.whatsapp') ?: (setting('contact.whatsapp') ? 'https://wa.me/'.setting('contact.whatsapp') : null))
                    @if(setting('social.facebook') || $followWhatsapp || setting('social.tiktok'))
                        <li class="pt-1">
                            <div class="mb-2 text-xs font-semibold uppercase tracking-widest text-white/50">Suivez-nous</div>
                            <div class="flex items-center gap-2">
                                @if(setting('social.facebook'))
                                    <a href="{{ setting('social.facebook') }}" target="_blank" rel="noopener" aria-label="SIBEA sur Facebook"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-cuivre hover:text-nuit">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                                        </svg>
                                    </a>
                                @endif
                                @if($followWhatsapp)
                                    <a href="{{ $followWhatsapp }}" target="_blank" rel="noopener" aria-label="SIBEA sur WhatsApp"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-cuivre hover:text-nuit">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
                                        </svg>
                                    </a>
                                @endif
                                @if(setting('social.tiktok'))
                                    <a href="{{ setting('social.tiktok') }}" target="_blank" rel="noopener" aria-label="SIBEA sur TikTok"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition-colors hover:bg-cuivre hover:text-nuit">
                                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                            <path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z" />
                                        </svg>
                                    </a>
                                @endif
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
