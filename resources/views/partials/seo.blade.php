@php
    use Spatie\SchemaOrg\Schema;

    $seoTitle = $title ?? 'Groupe SIBEA — BTP, Immobilier, Énergie, Agro-industrie';
    $seoDescription = $description ?? 'Le Groupe SIBEA rassemble des expertises complémentaires dans le BTP, l’immobilier, l’énergie et l’agro-industrie.';
    $canonical = $canonicalUrl ?? url()->current();

    $organization = Schema::organization()
        ->name('Groupe SIBEA')
        ->url(config('app.url'))
        ->logo(asset('images/logo-sibea.png'));

    $breadcrumbs = Schema::breadcrumbList()
        ->itemListElement([
            Schema::listItem()->position(1)->name('Accueil')->item(url('/')),
            Schema::listItem()->position(2)->name($seoTitle)->item($canonical),
        ]);
@endphp

<link rel="canonical" href="{{ $canonical }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">

{!! $organization->toScript() !!}
{!! $breadcrumbs->toScript() !!}
