<?php

use App\Models\Page;
use App\Support\PageSeo;
use Illuminate\Http\Request;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component
{
    public string $pageTitle = '';

    public string $pageContent = '';

    public string $heroImage = '';

    public function mount(Request $request): void
    {
        $bound = $request->route('page');
        $slug = $bound instanceof Page ? $bound->slug : ($bound ?? $request->route('page_slug'));

        $page = $slug !== null ? Page::query()->where('slug', $slug)->first() : null;

        if ($page !== null) {
            abort_unless($page->is_published, 404);

            $this->pageTitle = $page->title;
            $this->pageContent = (string) $page->content;
        } else {
            $fallback = config('site.pages', [])[$slug] ?? null;

            abort_if($fallback === null, 404);

            $this->pageTitle = $fallback['title'];
            $this->pageContent = $fallback['content'];
        }

        $this->heroImage = setting_media_url(
            $slug === 'politique-de-confidentialite' ? 'visuals.hero.privacy' : 'visuals.hero.legal'
        );

        PageSeo::share($this->pageTitle, PageSeo::excerpt($this->pageContent));
    }
};
?>

<div>
    <section class="on-dark relative bg-nuit text-white py-16 overflow-hidden">
        <img src="{{ $heroImage }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
        <div class="absolute inset-0" style="background: linear-gradient(90deg, rgba(11,31,51,0.92) 0%, rgba(11,31,51,0.65) 100%);"></div>
        <div class="relative max-w-4xl mx-auto px-6 lg:px-8">
            <div class="text-cuivre font-display font-semibold tracking-widest text-sm mb-4">{{ setting('general.site_name', 'GROUPE SIBEA') }}</div>
            <h1 class="font-display font-extrabold text-4xl lg:text-5xl">{{ $pageTitle }}</h1>
        </div>
    </section>

    <section class="max-w-4xl mx-auto px-6 lg:px-8 py-16">
        <div class="page-content text-anthracite">
            {!! Illuminate\Support\Str::markdown($pageContent, [
                'html_input' => 'strip',
                'allow_unsafe_links' => false,
            ]) !!}
        </div>
    </section>
</div>
