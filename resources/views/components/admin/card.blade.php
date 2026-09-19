@props([
    'title' => null,
    'subtitle' => null,
    'padded' => true,
])

<flux:card {{ $attributes->merge(['class' => 'p-0 rounded-md border-none! shadow-[0_1px_2px_rgba(57,62,80,0.15)]!']) }}>
    @if($title)
        <div class="flex items-center justify-between gap-3 border-b border-bordure border-dashed px-5 py-3">
            <div>
                <flux:heading size="lg" class="font-medium">{{ $title }}</flux:heading>
                @if($subtitle)
                    <flux:subheading>{{ $subtitle }}</flux:subheading>
                @endif
            </div>
            @isset($actions)
                <div class="flex items-center gap-2">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    @endif

    <div @class(['space-y-4 p-5' => $padded])>
        {{ $slot }}
    </div>
</flux:card>
