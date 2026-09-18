@props([
    'title' => null,
    'subtitle' => null,
])

<flux:card {{ $attributes->merge(['class' => 'p-0']) }}>
    @if($title)
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-bordure px-5 py-3">
            <div>
                <flux:heading size="lg">{{ $title }}</flux:heading>
                @if($subtitle)
                    <flux:subheading>{{ $subtitle }}</flux:subheading>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    <div class="space-y-4 p-5">
        {{ $slot }}
    </div>
</flux:card>
