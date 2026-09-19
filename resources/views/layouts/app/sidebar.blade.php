<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="theme-admin min-h-screen bg-[#EBF3F4] dark:bg-zinc-800">
        @php
            $newLeadsCount = auth()->user()->can('manage_leads') ? \App\Models\Lead::where('status', \App\Enums\LeadStatus::Nouveau)->count() : 0;
            $draftProjectsCount = auth()->user()->can('manage_projects') ? \App\Models\Project::where('is_published', false)->count() : 0;
            $draftPagesCount = auth()->user()->can('manage_pages') ? \App\Models\Page::where('is_published', false)->count() : 0;
        @endphp
        <flux:sidebar sticky collapsible="mobile" class="border-e border-white/10 bg-ynex dark:border-white/10 dark:bg-ynex">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('admin.dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('admin.dashboard')" :current="request()->routeIs('admin.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.nav>
                @canany(['manage_sectors', 'manage_expertises', 'manage_services', 'manage_projects', 'manage_testimonials', 'manage_statistics', 'manage_pages'])
                    <flux:sidebar.group heading="Contenus" icon="squares-2x2" expandable :expanded="true" class="grid">
                    @can('manage_sectors')
                        <flux:sidebar.item icon="building-office-2" :href="route('admin.sectors')" :current="request()->routeIs('admin.sectors')" wire:navigate>
                            Secteurs
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_expertises')
                        <flux:sidebar.item icon="academic-cap" :href="route('admin.expertises')" :current="request()->routeIs('admin.expertises')" wire:navigate>
                            Expertises
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_services')
                        <flux:sidebar.item icon="wrench-screwdriver" :href="route('admin.services')" :current="request()->routeIs('admin.services')" wire:navigate>
                            Services
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_projects')
                        <flux:sidebar.item icon="photo" :href="route('admin.projects')" :current="request()->routeIs('admin.projects')" wire:navigate :badge="$draftProjectsCount > 0 ? $draftProjectsCount : null" badge-color="zinc">
                            Réalisations
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_testimonials')
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('admin.testimonials')" :current="request()->routeIs('admin.testimonials')" wire:navigate>
                            Témoignages
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_statistics')
                        <flux:sidebar.item icon="chart-bar" :href="route('admin.statistics')" :current="request()->routeIs('admin.statistics')" wire:navigate>
                            Chiffres clés
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_pages')
                        <flux:sidebar.item icon="document-text" :href="route('admin.pages')" :current="request()->routeIs('admin.pages')" wire:navigate :badge="$draftPagesCount > 0 ? $draftPagesCount : null" badge-color="zinc">
                            Pages
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany
            </flux:sidebar.nav>

            @can('manage_leads')
            <flux:sidebar.nav>
                <flux:sidebar.group heading="Prospects" icon="users" class="grid">
                    <flux:sidebar.item icon="inbox" :href="route('admin.leads')" :current="request()->routeIs('admin.leads')" wire:navigate :badge="$newLeadsCount > 0 ? $newLeadsCount : null" badge-color="red">
                        Prospects
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>
            @endcan

            @canany(['manage_users', 'manage_roles', 'manage_settings'])
            <flux:sidebar.nav>
                <flux:sidebar.group heading="Administration" icon="cog-6-tooth" expandable :expanded="false" class="grid">
                    @can('manage_users')
                        <flux:sidebar.item icon="user-group" :href="route('admin.users')" :current="request()->routeIs('admin.users')" wire:navigate>
                            Utilisateurs
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_roles')
                        <flux:sidebar.item icon="shield-check" :href="route('admin.roles')" :current="request()->routeIs('admin.roles')" wire:navigate>
                            Rôles
                        </flux:sidebar.item>
                    @endcan
                    @can('manage_settings')
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.settings')" :current="request()->routeIs('admin.settings')" wire:navigate>
                            Paramètres du site
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            </flux:sidebar.nav>
            @endcanany

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="globe-alt" :href="route('home')" target="_blank">
                    Voir le site
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @persist('toast')
            <flux:toast.group>
                <flux:toast />
            </flux:toast.group>
        @endpersist

        @fluxScripts
    </body>
</html>
