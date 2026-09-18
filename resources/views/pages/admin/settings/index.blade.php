<?php

use App\Concerns\AddsMediaFromUploads;
use App\Models\Sector;
use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts::app')] #[Title('Paramètres du site')] class extends Component
{
    use AddsMediaFromUploads, WithFileUploads;

    private const TEXT_SETTINGS = [
        'site_name' => 'general.site_name',
        'footer_tagline' => 'general.footer_tagline',
        'footer_copyright' => 'general.footer_copyright',
        'header_sectors_label' => 'header.link.sectors.label',
        'header_expertises_label' => 'header.link.expertises.label',
        'header_projects_label' => 'header.link.projects.label',
        'header_contact_label' => 'header.link.contact.label',
        'header_login_label' => 'header.login_label',
        'seo_title' => 'seo.title',
        'seo_description' => 'seo.description',
        'hero_title' => 'home.hero.title',
        'hero_subtitle' => 'home.hero.subtitle',
        'hero_primary_label' => 'home.hero.primary_label',
        'rse_title' => 'home.rse.title',
        'rse_text' => 'home.rse.text',
        'rse_button' => 'home.rse.button',
        'sectors_title' => 'home.sectors.title',
        'sectors_subtitle' => 'home.sectors.subtitle',
        'group_title' => 'home.group.title',
        'group_text' => 'home.group.text',
        'group_quote' => 'home.group.quote',
        'group_button' => 'home.group.button',
        'expertises_title' => 'home.expertises.title',
        'expertises_subtitle' => 'home.expertises.subtitle',
        'expertise_cta_title' => 'home.expertise_cta.title',
        'expertise_cta_subtitle' => 'home.expertise_cta.subtitle',
        'projects_title' => 'home.projects.title',
        'method_title' => 'home.method.title',
        'testimonials_title' => 'home.testimonials.title',
        'cta_title' => 'home.cta.title',
        'cta_subtitle' => 'home.cta.subtitle',
        'cta_button' => 'home.cta.button',
        'sectors_hero_title' => 'sectors.hero.title',
        'sectors_hero_subtitle' => 'sectors.hero.subtitle',
        'expertises_hero_title' => 'expertises.hero.title',
        'expertises_hero_subtitle' => 'expertises.hero.subtitle',
        'expertises_cta_title' => 'expertises.cta.title',
        'expertises_cta_subtitle' => 'expertises.cta.subtitle',
        'expertise_cta_eyebrow' => 'home.expertise_cta.eyebrow',
        'expertise_cta_points' => 'home.expertise_cta.points',
        'expertise_cta_whatsapp_label' => 'home.expertise_cta.whatsapp_label',
        'projects_hero_title' => 'projects.hero.title',
        'projects_hero_subtitle' => 'projects.hero.subtitle',
        'projects_empty_title' => 'projects.empty.title',
        'projects_empty_text' => 'projects.empty.text',
        'contact_hero_subtitle' => 'contact.hero.subtitle',
        'contact_address' => 'contact.address',
        'contact_phone' => 'contact.phone',
        'contact_phone_link' => 'contact.phone_link',
        'contact_email' => 'contact.email',
        'contact_hours' => 'contact.hours',
        'contact_whatsapp' => 'contact.whatsapp',
        'contact_whatsapp_message' => 'contact.whatsapp_message',
        'contact_latitude' => 'contact.latitude',
        'contact_longitude' => 'contact.longitude',
        'office_title' => 'contact.office_title',
        'office_location' => 'contact.office_location',
        'office_description' => 'contact.office_description',
    ];

    private const VISUAL_SETTINGS = [
        'logo' => 'visuals.logo',
        'hero_home' => 'visuals.hero.home',
        'group_image' => 'visuals.group',
        'hero_sectors' => 'visuals.hero.sectors',
        'hero_expertises' => 'visuals.hero.expertises',
        'hero_projects' => 'visuals.hero.projects',
        'hero_contact' => 'visuals.hero.contact',
        'hero_legal' => 'visuals.hero.legal',
        'hero_privacy' => 'visuals.hero.privacy',
    ];

    /** @var array<string, string> */
    public array $texts = [];

    /** @var array<int, array{title: string, text: string}> */
    public array $method_steps = [];

    /**
     * @var array<int, array{hero_title: string|null, hero_description: string|null, hero_cta_label: string|null}>
     */
    public array $slides = [];

    /** @var array<int, mixed> */
    public array $heroImages = [];

    /** @var array<string, bool> */
    public array $headerLinks = [
        'sectors' => true,
        'expertises' => true,
        'projects' => true,
        'contact' => true,
    ];

    public $logo = null;

    public $hero_home = null;

    public $group_image = null;

    public $hero_sectors = null;

    public $hero_expertises = null;

    public $hero_projects = null;

    public $hero_contact = null;

    public $hero_legal = null;

    public $hero_privacy = null;

    public bool $saved = false;

    public function mount(): void
    {
        $this->authorize('manage', Setting::class);

        foreach (array_keys(self::TEXT_SETTINGS) as $property) {
            $this->texts[$property] = (string) setting(self::TEXT_SETTINGS[$property]);
        }

        $this->method_steps = setting_array('home.method_steps');

        $this->headerLinks = [
            'sectors' => setting('header.link.sectors.visible') === '1',
            'expertises' => setting('header.link.expertises.visible') === '1',
            'projects' => setting('header.link.projects.visible') === '1',
            'contact' => setting('header.link.contact.visible') === '1',
        ];

        foreach (Sector::active()->ordered()->get() as $sector) {
            $this->slides[$sector->id] = [
                'hero_title' => $sector->hero_title,
                'hero_description' => $sector->hero_description,
                'hero_cta_label' => $sector->hero_cta_label,
            ];
        }
    }

    public function heroSectors()
    {
        return Sector::active()->ordered()->get();
    }

    public function updated(): void
    {
        $this->saved = false;
    }

    public function addMethodStep(): void
    {
        $this->method_steps[] = ['title' => '', 'text' => ''];
    }

    public function removeMethodStep(int $index): void
    {
        unset($this->method_steps[$index]);
        $this->method_steps = array_values($this->method_steps);
    }

    public function moveMethodStep(int $index, string $direction): void
    {
        $this->moveItem($this->method_steps, $index, $direction);
    }

    public function removeVisual(string $property): void
    {
        $this->authorize('manage', Setting::class);

        $key = self::VISUAL_SETTINGS[$property] ?? null;

        if ($key === null) {
            return;
        }

        Setting::removeFile($key);
        $this->saved = true;
    }

    public function save(): void
    {
        $this->authorize('manage', Setting::class);

        $this->validate([
            'texts.*' => ['nullable', 'string'],
            'texts.contact_email' => ['nullable', 'email'],
            'texts.contact_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'texts.contact_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'method_steps' => ['array'],
            'method_steps.*.title' => ['nullable', 'string', 'max:255'],
            'method_steps.*.text' => ['nullable', 'string', 'max:500'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'hero_sectors' => ['nullable', 'image', 'max:5120'],
            'hero_expertises' => ['nullable', 'image', 'max:5120'],
            'hero_projects' => ['nullable', 'image', 'max:5120'],
            'hero_contact' => ['nullable', 'image', 'max:5120'],
            'hero_legal' => ['nullable', 'image', 'max:5120'],
            'hero_privacy' => ['nullable', 'image', 'max:5120'],
            'slides.*.hero_title' => ['nullable', 'string', 'max:255'],
            'slides.*.hero_description' => ['nullable', 'string'],
            'slides.*.hero_cta_label' => ['nullable', 'string', 'max:255'],
            'heroImages.*' => ['nullable', 'image', 'max:5120'],
            'headerLinks' => ['array'],
            'headerLinks.*' => ['boolean'],
        ]);

        foreach (self::TEXT_SETTINGS as $property => $key) {
            Setting::put($key, (string) ($this->texts[$property] ?? ''));
        }

        foreach ($this->headerLinks as $link => $visible) {
            Setting::put("header.link.{$link}.visible", $visible ? '1' : '0');
        }

        Setting::put('home.method_steps', json_encode($this->cleanItems($this->method_steps)));

        foreach (self::VISUAL_SETTINGS as $property => $key) {
            if ($this->{$property}) {
                Setting::putFile($key, $this->{$property});
                $this->{$property} = null;
            }
        }

        foreach ($this->slides as $id => $slide) {
            $sector = Sector::findOrFail($id);

            $sector->update([
                'hero_title' => $slide['hero_title'] ?: null,
                'hero_description' => $slide['hero_description'] ?: null,
                'hero_cta_label' => $slide['hero_cta_label'] ?: null,
            ]);

            if (! empty($this->heroImages[$id])) {
                $image = $this->heroImages[$id];
                $sector->clearMediaCollection('hero');
                static::addMediaFromUpload($sector, $image, 'hero');
                unset($this->heroImages[$id]);
            }
        }

        $this->saved = true;
    }

    public function removeHeroImage(int $sectorId): void
    {
        $sector = Sector::findOrFail($sectorId);

        $sector->clearMediaCollection('hero');
        unset($this->heroImages[$sectorId]);
        $this->saved = true;
    }

    /**
     * @param  array<int, array{title: string, text: string}>  $items
     * @return array<int, array{title: string, text: string}>
     */
    private function cleanItems(array $items): array
    {
        return collect($items)
            ->map(fn ($item) => [
                'title' => (string) ($item['title'] ?? ''),
                'text' => (string) ($item['text'] ?? ''),
            ])
            ->filter(fn ($item) => $item['title'] !== '' || $item['text'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array{title: string, text: string}>  $items
     */
    private function moveItem(array &$items, int $index, string $direction): void
    {
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (! isset($items[$index]) || ! isset($items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        $items = array_values($items);
    }
};
?>

<div class="p-6" x-data="{ tab: 'general' }">
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="xl">Paramètres du site</flux:heading>
            <flux:subheading>Textes, coordonnées, SEO et visuels du site vitrine.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="save" icon="check">Enregistrer</flux:button>
    </div>

    @if($saved)
        <flux:callout variant="success" icon="check-circle" class="mb-6">Paramètres enregistrés.</flux:callout>
    @endif

    <div class="mb-6 flex flex-wrap gap-2">
        @foreach(['general' => 'Général & accueil', 'pages' => 'Pages', 'header' => 'Header', 'contact' => 'Contact & localisation', 'seo' => 'SEO', 'visuals' => 'Visuels'] as $key => $label)
            <button
                type="button"
                x-on:click="tab = '{{ $key }}'"
                x-bind:class="tab === '{{ $key }}' ? 'border-cuivre bg-cuivre text-nuit' : 'border-zinc-200 text-zinc-600 hover:border-cuivre dark:border-zinc-700 dark:text-zinc-300'"
                class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors"
            >{{ $label }}</button>
        @endforeach

    </div>

    <form wire:submit="save" class="space-y-6">
        <div x-show="tab === 'general'" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Identité</flux:heading>
                <flux:field>
                    <flux:label>Nom affiché (surtitres)</flux:label>
                    <flux:input wire:model="texts.site_name" type="text" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Textes du hero</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.hero_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Accroche (2 lignes max)</flux:label>
                    <flux:textarea wire:model="texts.hero_subtitle" rows="2" />
                </flux:field>
                <flux:field>
                    <flux:label>Bouton principal</flux:label>
                    <flux:input wire:model="texts.hero_primary_label" type="text" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Footer</flux:heading>
                <flux:field>
                    <flux:label>Baseline</flux:label>
                    <flux:input wire:model="texts.footer_tagline" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Copyright (sans l’année)</flux:label>
                    <flux:input wire:model="texts.footer_copyright" type="text" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Section secteurs</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.sectors_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Sous-titre</flux:label>
                    <flux:textarea wire:model="texts.sectors_subtitle" rows="2" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Section expertises</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.expertises_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Sous-titre</flux:label>
                    <flux:textarea wire:model="texts.expertises_subtitle" rows="2" />
                </flux:field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Encart — surtitre</flux:label>
                        <flux:input wire:model="texts.expertise_cta_eyebrow" type="text" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Encart — titre</flux:label>
                        <flux:input wire:model="texts.expertise_cta_title" type="text" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Encart — texte</flux:label>
                        <flux:input wire:model="texts.expertise_cta_subtitle" type="text" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Encart — points (un par ligne)</flux:label>
                    <flux:textarea wire:model="texts.expertise_cta_points" rows="2" />
                </flux:field>
                <flux:field>
                    <flux:label>Encart — libellé WhatsApp</flux:label>
                    <flux:input wire:model="texts.expertise_cta_whatsapp_label" type="text" />
                    <flux:description>Le bouton n’apparaît que si un numéro WhatsApp est renseigné dans Contact &amp; localisation.</flux:description>
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Bloc Le Groupe (accueil)</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.group_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Texte</flux:label>
                    <flux:textarea wire:model="texts.group_text" rows="3" />
                </flux:field>
                <flux:field>
                    <flux:label>Citation du Président</flux:label>
                    <flux:textarea wire:model="texts.group_quote" rows="2" />
                </flux:field>
                <flux:field>
                    <flux:label>Libellé du bouton</flux:label>
                    <flux:input wire:model="texts.group_button" type="text" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Section réalisations & témoignages</flux:heading>
                <flux:field>
                    <flux:label>Titre réalisations</flux:label>
                    <flux:input wire:model="texts.projects_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Titre témoignages</flux:label>
                    <flux:input wire:model="texts.testimonials_title" type="text" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Section RSE</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.rse_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Texte</flux:label>
                    <flux:textarea wire:model="texts.rse_text" rows="4" />
                </flux:field>
                <flux:field>
                    <flux:label>Libellé du bouton</flux:label>
                    <flux:input wire:model="texts.rse_button" type="text" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Notre méthode</flux:heading>
                <flux:field>
                    <flux:label>Titre de la section</flux:label>
                    <flux:input wire:model="texts.method_title" type="text" />
                </flux:field>
                <div class="space-y-3">
                    @foreach($method_steps as $index => $step)
                        <div wire:key="method-{{ $index }}" class="grid gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-zinc-700">
                            <flux:input wire:model="method_steps.{{ $index }}.title" placeholder="Titre (ex. Écoute & étude)" />
                            <flux:input wire:model="method_steps.{{ $index }}.text" placeholder="Description" />
                            <div class="flex items-center gap-1">
                                <flux:button size="xs" icon="chevron-up" wire:click="moveMethodStep({{ $index }}, 'up')" aria-label="Monter" />
                                <flux:button size="xs" icon="chevron-down" wire:click="moveMethodStep({{ $index }}, 'down')" aria-label="Descendre" />
                                <flux:button size="xs" variant="danger" icon="trash" wire:click="removeMethodStep({{ $index }})" aria-label="Supprimer" />
                            </div>
                        </div>
                    @endforeach
                </div>
                <flux:button size="sm" icon="plus" wire:click="addMethodStep">Ajouter une étape</flux:button>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Appel à l’action final</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.cta_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Texte</flux:label>
                    <flux:textarea wire:model="texts.cta_subtitle" rows="2" />
                </flux:field>
                <flux:field>
                    <flux:label>Bouton</flux:label>
                    <flux:input wire:model="texts.cta_button" type="text" />
                </flux:field>
            </flux:card>
        </div>

        <div x-show="tab === 'header'" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Logo</flux:heading>
                <div class="flex items-center gap-4">
                    <img src="{{ setting_media_url('visuals.logo') }}" alt="" class="h-12 w-auto rounded bg-white p-1">
                    <div class="flex-1">
                        <input type="file" wire:model="logo" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                        <flux:error name="logo" />
                    </div>
                    <flux:button size="sm" variant="danger" wire:click="removeVisual('logo')">Retirer</flux:button>
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Menu principal</flux:heading>
                <div class="space-y-3">
                    @foreach([
                        'sectors' => ['hint' => 'Secteurs d’activité', 'property' => 'header_sectors_label'],
                        'expertises' => ['hint' => 'Expertises', 'property' => 'header_expertises_label'],
                        'projects' => ['hint' => 'Réalisations', 'property' => 'header_projects_label'],
                        'contact' => ['hint' => 'Contact', 'property' => 'header_contact_label'],
                    ] as $link => $linkConfig)
                        <div wire:key="header-link-{{ $link }}" class="grid items-center gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[auto_1fr] dark:border-zinc-700">
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="headerLinks.{{ $link }}" />
                                Visible
                            </label>
                            <flux:input wire:model="texts.{{ $linkConfig['property'] }}" type="text" :placeholder="$linkConfig['hint']" />
                        </div>
                    @endforeach
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Connexion administration</flux:heading>
                <flux:field>
                    <flux:label>Libellé du lien</flux:label>
                    <flux:input wire:model="texts.header_login_label" type="text" />
                </flux:field>
            </flux:card>
        </div>

        <div x-show="tab === 'pages'" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Page secteurs d’activité</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.sectors_hero_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Sous-titre</flux:label>
                    <flux:textarea wire:model="texts.sectors_hero_subtitle" rows="2" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Page expertises</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.expertises_hero_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Sous-titre (:count remplacé par le nombre d’expertises)</flux:label>
                    <flux:textarea wire:model="texts.expertises_hero_subtitle" rows="2" />
                </flux:field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Encart — titre</flux:label>
                        <flux:input wire:model="texts.expertises_cta_title" type="text" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Encart — texte</flux:label>
                        <flux:input wire:model="texts.expertises_cta_subtitle" type="text" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Page réalisations</flux:heading>
                <flux:field>
                    <flux:label>Titre</flux:label>
                    <flux:input wire:model="texts.projects_hero_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Sous-titre</flux:label>
                    <flux:textarea wire:model="texts.projects_hero_subtitle" rows="2" />
                </flux:field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>État vide — titre</flux:label>
                        <flux:input wire:model="texts.projects_empty_title" type="text" />
                    </flux:field>
                    <flux:field>
                        <flux:label>État vide — texte</flux:label>
                        <flux:input wire:model="texts.projects_empty_text" type="text" />
                    </flux:field>
                </div>
            </flux:card>
            <flux:heading size="lg">Hero des secteurs d’activité</flux:heading>

            @foreach($this->heroSectors() as $sector)
                <flux:card wire:key="hero-slide-{{ $sector->id }}" class="space-y-4">
                    <flux:heading size="lg">Hero — {{ $sector->name }}</flux:heading>

                    <div class="grid gap-4 sm:grid-cols-[16rem_1fr]">
                        <div class="space-y-2">
                            @php($upload = $heroImages[$sector->id] ?? null)
                            @if($upload)
                                <img src="{{ $upload->temporaryUrl() }}" alt="" class="h-32 w-full rounded object-cover">
                            @elseif($sector->getFirstMediaUrl('hero', 'thumb'))
                                <img src="{{ $sector->getFirstMediaUrl('hero', 'thumb') }}" alt="" class="h-32 w-full rounded object-cover">
                            @else
                                <div class="flex h-32 w-full items-center justify-center rounded bg-nuit text-xs text-white/60">Aucune image</div>
                            @endif
                            <input type="file" wire:model="heroImages.{{ $sector->id }}" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                            <flux:error name="heroImages.{{ $sector->id }}" />
                            <flux:button size="xs" variant="danger" wire:click="removeHeroImage({{ $sector->id }})">Retirer l’image</flux:button>
                        </div>

                        <div class="space-y-4">
                            <flux:field>
                                <flux:label>Titre</flux:label>
                                <flux:input wire:model="slides.{{ $sector->id }}.hero_title" type="text" />
                                <flux:error name="slides.{{ $sector->id }}.hero_title" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="slides.{{ $sector->id }}.hero_description" rows="2" />
                                <flux:error name="slides.{{ $sector->id }}.hero_description" />
                            </flux:field>
                            <flux:field>
                                <flux:label>Libellé du bouton</flux:label>
                                <flux:input wire:model="slides.{{ $sector->id }}.hero_cta_label" type="text" />
                                <flux:error name="slides.{{ $sector->id }}.hero_cta_label" />
                            </flux:field>
                        </div>
                    </div>
                </flux:card>
            @endforeach


        </div>

        <div x-show="tab === 'contact'" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Coordonnées</flux:heading>
                <flux:field>
                    <flux:label>Sous-titre de la page contact</flux:label>
                    <flux:input wire:model="texts.contact_hero_subtitle" type="text" />
                </flux:field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Adresse (une ligne par ligne)</flux:label>
                        <flux:textarea wire:model="texts.contact_address" rows="2" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Horaires (une ligne par ligne)</flux:label>
                        <flux:textarea wire:model="texts.contact_hours" rows="2" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Téléphone affiché</flux:label>
                        <flux:input wire:model="texts.contact_phone" type="text" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Téléphone pour le lien tel:</flux:label>
                        <flux:input wire:model="texts.contact_phone_link" type="text" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input wire:model="texts.contact_email" type="email" />
                        <flux:error name="texts.contact_email" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Numéro WhatsApp (indicatif sans +)</flux:label>
                        <flux:input wire:model="texts.contact_whatsapp" type="text" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Message WhatsApp pré-rempli</flux:label>
                    <flux:textarea wire:model="texts.contact_whatsapp_message" rows="2" />
                </flux:field>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Localisation</flux:heading>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Latitude</flux:label>
                        <flux:input wire:model="texts.contact_latitude" type="text" placeholder="Ex. 5.35" />
                        <flux:error name="texts.contact_latitude" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Longitude</flux:label>
                        <flux:input wire:model="texts.contact_longitude" type="text" placeholder="Ex. -4.00" />
                        <flux:error name="texts.contact_longitude" />
                    </flux:field>
                </div>
            </flux:card>

            <flux:card class="space-y-4">
                <flux:heading size="lg">Implantation</flux:heading>
                <flux:field>
                    <flux:label>Titre du bloc</flux:label>
                    <flux:input wire:model="texts.office_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Ville / zone</flux:label>
                    <flux:input wire:model="texts.office_location" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="texts.office_description" rows="3" />
                </flux:field>
            </flux:card>
        </div>

        <div x-show="tab === 'seo'" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Référencement par défaut</flux:heading>
                <flux:field>
                    <flux:label>Titre meta</flux:label>
                    <flux:input wire:model="texts.seo_title" type="text" />
                </flux:field>
                <flux:field>
                    <flux:label>Description meta</flux:label>
                    <flux:textarea wire:model="texts.seo_description" rows="3" />
                </flux:field>
            </flux:card>
        </div>

        <div x-show="tab === 'visuals'" class="space-y-6">
            <flux:card class="space-y-4">
                <flux:heading size="lg">Images de fond des heroes</flux:heading>
                <div class="grid gap-6 sm:grid-cols-2">
                    @foreach([
                        'hero_home' => ['label' => 'Accueil', 'key' => 'visuals.hero.home'],
                        'group_image' => ['label' => 'Bloc Le Groupe (accueil)', 'key' => 'visuals.group'],
                        'hero_sectors' => ['label' => 'Page secteurs', 'key' => 'visuals.hero.sectors'],
                        'hero_expertises' => ['label' => 'Page expertises', 'key' => 'visuals.hero.expertises'],
                        'hero_projects' => ['label' => 'Page réalisations', 'key' => 'visuals.hero.projects'],
                        'hero_contact' => ['label' => 'Page contact', 'key' => 'visuals.hero.contact'],
                        'hero_legal' => ['label' => 'Mentions légales', 'key' => 'visuals.hero.legal'],
                        'hero_privacy' => ['label' => 'Politique de confidentialité', 'key' => 'visuals.hero.privacy'],
                    ] as $property => $visual)
                        <div wire:key="visual-{{ $property }}" class="space-y-2">
                            <div class="text-sm font-medium">{{ $visual['label'] }}</div>
                            <img src="{{ setting_media_url($visual['key']) }}" alt="" class="h-24 w-full rounded object-cover">
                            <input type="file" wire:model="{{ $property }}" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                            <flux:error name="{{ $property }}" />
                            <flux:button size="xs" variant="danger" wire:click="removeVisual('{{ $property }}')">Retirer</flux:button>
                        </div>
                    @endforeach
                </div>
            </flux:card>
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="check">Enregistrer</flux:button>
        </div>
    </form>
</div>
