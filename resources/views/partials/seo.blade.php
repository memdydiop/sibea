@php
    use Spatie\SchemaOrg\Schema;

    $seoTitle = $title ?? setting('seo.title');
    $seoDescription = $description ?? setting('seo.description');
    $canonical = $canonicalUrl ?? url()->current();
    $seoImage = $ogImage ?? setting_media_url('visuals.hero.home', 'hero');

    $organization = Schema::organization()
        ->name('Groupe SIBEA')
        ->url(url('/'))
        ->logo(asset('images/logo-sibea.png'));

    $breadcrumbs = Schema::breadcrumbList()
        ->itemListElement([
            Schema::listItem()->position(1)->name('Accueil')->item(url('/')),
            Schema::listItem()->position(2)->name($seoTitle)->item($canonical),
        ]);
@endphp

<link rel="canonical" href="{{ $canonical }}">
<meta name="robots" content="index, follow">
<meta property="og:site_name" content="Groupe SIBEA">
<meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">

{!! $organization->toScript() !!}
{!! $breadcrumbs->toScript() !!}
