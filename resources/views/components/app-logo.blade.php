@props([
    'sidebar' => false,
])

@php($brandLogo = setting_media_url('visuals.logo'))

@if($sidebar)
    <flux:sidebar.brand {{ $attributes->class('justify-center') }}>
        <x-slot name="logo" class="flex h-11 items-center rounded-md bg-white px-2">
            <img src="{{ $brandLogo }}" alt="Groupe SIBEA" class="h-8 w-auto">
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('app.name', 'Groupe SIBEA')" {{ $attributes }}>
        <x-slot name="logo" class="flex h-8 items-center rounded-md bg-white px-1.5">
            <img src="{{ $brandLogo }}" alt="Groupe SIBEA" class="h-5 w-auto">
        </x-slot>
    </flux:brand>
@endif
