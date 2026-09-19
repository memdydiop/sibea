{{--
    Barre d'outils des listes admin (recherche + pagination + filtres).

    Contrat : le composant Livewire parent expose $search (string) et $perPage (int).
    Les filtres spécifiques à chaque liste passent par le slot "filters".

    <x-admin.toolbar search-placeholder="Rechercher…">
        <x-slot:filters>
            <flux:select wire:model.live="statusFilter" size="sm">…</flux:select>
        </x-slot:filters>
    </x-admin.toolbar>
--}}
@props([
    'searchPlaceholder' => 'Rechercher…',
])

<div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-4 pb-3">
    <div class="flex items-center gap-3">
        <flux:input wire:model.live="search" type="search" size="sm" :placeholder="$searchPlaceholder" class="w-56" />
        <flux:select wire:model.live="perPage" size="sm" class="w-20">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="20">20</option>
        </flux:select>
    </div>
    @isset($filters)
        <div class="flex items-center gap-3">
            {{ $filters }}
        </div>
    @endisset
</div>
