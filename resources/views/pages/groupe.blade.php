<?php

use App\Models\Page;
use App\Models\Setting;
use App\Support\PageSeo;
use Illuminate\Support\Facades\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public string $pageTitle = '';

    public string $pageContent = '';

    public string $heroImage = '';

    public string $presidentPhoto = '';

    /** @var array<int, string> */
    public array $presidentParagraphs = [];

    public function mount(): void
    {
        $page = Page::query()->where('slug', 'le-groupe')->first();

        if ($page !== null) {
            abort_unless($page->is_published, 404);

            $this->pageTitle = $page->title;
            $this->pageContent = (string) $page->content;
        } else {
            $fallback = config('site.pages', [])['le-groupe'] ?? null;

            abort_if($fallback === null, 404);

            $this->pageTitle = $fallback['title'];
            $this->pageContent = $fallback['content'];
        }

        $this->heroImage = setting_media_url('visuals.group', 'hero');

        PageSeo::share($page?->meta_title ?: $this->pageTitle, $page?->meta_description ?: PageSeo::excerpt($this->pageContent));
        View::share('ogImage', setting_media_url('visuals.group', 'hero') ?: null);

        $this->presidentPhoto = Setting::query()->where('key', 'visuals.president')->first()?->hasMedia('file')
            ? setting_media_url('visuals.president')
            : '';

        $this->presidentParagraphs = array_values(array_filter(
            preg_split('/\R{2,}/', (string) setting('group.president.text')) ?: [],
            fn (string $paragraph): bool => trim($paragraph) !== '',
        ));
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white overflow-hidden">
        <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="eager" decoding="async" fetchpriority="high">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-7xl mx-auto px-6 lg:px-8 pt-24 pb-14">
            <nav aria-label="Fil d’Ariane" class="mb-8">
                <ol class="flex flex-wrap items-center gap-2 text-sm text-white/60">
                    <li><a href="{{ route('home') }}" class="transition-colors hover:text-cuivre" wire:navigate>Accueil</a></li>
                    <li aria-hidden="true">/</li>
                    <li class="text-white/90" aria-current="page">{{ $pageTitle }}</li>
                </ol>
            </nav>
            <h1 class="font-display font-extrabold text-4xl lg:text-6xl mb-6 max-w-4xl">{{ $pageTitle }}</h1>
        </div>
    </section>

    @if($presidentParagraphs !== [])
        <section class="bg-surface">
            <div class="max-w-4xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
                <div class="text-accroche font-display font-semibold tracking-widest text-sm mb-4">ÉDITO</div>
                <h2 class="font-display font-extrabold text-nuit tracking-tight text-3xl lg:text-4xl mb-8">{{ setting('group.president.title') }}</h2>
                <div class="relative overflow-hidden rounded-lg border border-bordure bg-casse p-8 lg:p-10">
                    <span aria-hidden="true" class="pointer-events-none absolute -top-6 right-4 font-display text-[10rem] font-extrabold leading-none text-cuivre/15">“</span>
                    <div class="relative flex flex-col gap-8 md:flex-row md:items-start">
                        @if($presidentPhoto !== '')
                            <img src="{{ $presidentPhoto }}" alt="Photo du Président" loading="lazy" decoding="async" class="h-56 w-full shrink-0 rounded-lg object-cover object-top md:h-72 md:w-60">
                        @endif
                        <div class="min-w-0">
                            <div class="space-y-5 text-lg leading-relaxed text-anthracite">
                                @foreach($presidentParagraphs as $paragraph)
                                    <p>{!! nl2br(e($paragraph)) !!}</p>
                                @endforeach
                            </div>
                            <p class="mt-8 flex items-center gap-3 text-sm">
                                <span aria-hidden="true" class="h-px w-8 bg-cuivre"></span>
                                <span class="font-display font-semibold text-nuit">{{ setting('group.president.name') ?: 'Le Président Directeur Général' }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    @endif

    <section class="max-w-4xl mx-auto px-6 lg:px-8 py-16">
        <div class="page-content text-anthracite">
            {!! Illuminate\Support\Str::markdown($pageContent, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]) !!}
        </div>
    </section>
</div>
