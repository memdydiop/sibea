<?php

use App\Concerns\AddsMediaFromUploads;
use App\Support\UniqueSlug;
use App\Models\Sector;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Secteurs')] class extends Component {
    use AddsMediaFromUploads, WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 10;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    public bool $is_active = true;

    public bool $is_locked = false;

    public int $sort_order = 0;

    public ?string $hero_title = null;

    public ?string $hero_description = null;

    public ?string $hero_cta_label = null;

    public $heroImage = null;

    public ?string $intro_title = null;

    public ?string $intro_text = null;

    /** @var array<int, array{title: string, text: string}> */
    public array $cards = [];

    /** @var array<int, array{value: string, label: string}> */
    public array $figures = [];

    public ?string $cta_title = null;

    public ?string $cta_text = null;

    public ?string $cta_label = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Sector::class);
    }

    #[Computed]
    public function sectors()
    {
        return Sector::query()
            ->when($this->search, fn($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($this->search) . '%']))
            ->when($this->statusFilter === 'active', fn($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn($query) => $query->where('is_active', false))
            ->ordered()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function editedSector(): ?Sector
    {
        return $this->editingId ? Sector::find($this->editingId) : null;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function updatedName(): void
    {
        $isAutomaticSlug = $this->editingId === null;

        if (!$isAutomaticSlug) {
            $sector = Sector::find($this->editingId);
            $isAutomaticSlug = $sector !== null && $this->slug === $sector->slug && $sector->slug === Str::slug($sector->name);
        }

        if ($isAutomaticSlug) {
            $this->slug = UniqueSlug::for(Sector::class, $this->name, $this->editingId);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Sector::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $sector = Sector::findOrFail($id);
        $this->authorize('update', $sector);

        $this->editingId = $sector->id;
        $this->name = $sector->name;
        $this->slug = $sector->slug;
        $this->short_description = $sector->short_description;
        $this->description = $sector->description;
        $this->is_active = $sector->is_active;
        $this->is_locked = $sector->is_locked;
        $this->sort_order = $sector->sort_order;
        $this->hero_title = $sector->hero_title;
        $this->hero_description = $sector->hero_description;
        $this->hero_cta_label = $sector->hero_cta_label;
        $this->intro_title = $sector->page_intro_title;
        $this->intro_text = $sector->page_intro_text;
        $this->cards = $sector->page_cards ?: [];
        $this->figures = $sector->page_figures ?: [];
        $this->cta_title = $sector->page_cta_title;
        $this->cta_text = $sector->page_cta_text;
        $this->cta_label = $sector->page_cta_label;
        $this->heroImage = null;
        $this->showForm = true;
    }

    public function save(): void
    {
        if (trim($this->slug) === '') {
            $this->slug = UniqueSlug::for(Sector::class, $this->name, $this->editingId);
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('sectors', 'slug')->ignore($this->editingId)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_locked' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'hero_title' => ['nullable', 'string', 'max:255'],
            'hero_description' => ['nullable', 'string'],
            'hero_cta_label' => ['nullable', 'string', 'max:255'],
            'heroImage' => ['nullable', 'image', 'max:5120'],
            'intro_title' => ['nullable', 'string', 'max:255'],
            'intro_text' => ['nullable', 'string'],
            'cards' => ['array', 'max:8'],
            'cards.*.title' => ['nullable', 'string', 'max:255'],
            'cards.*.text' => ['nullable', 'string', 'max:500'],
            'figures' => ['array', 'max:4'],
            'figures.*.value' => ['nullable', 'string', 'max:255'],
            'figures.*.label' => ['nullable', 'string', 'max:255'],
            'cta_title' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:255'],
        ]);

        $data = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'short_description' => $validated['short_description'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_active' => $validated['is_active'],
            'is_locked' => $validated['is_locked'],
            'sort_order' => $validated['sort_order'],
            'hero_title' => $this->hero_title ?: null,
            'hero_description' => $this->hero_description ?: null,
            'hero_cta_label' => $this->hero_cta_label ?: null,
            'page_intro_title' => $this->intro_title ?: null,
            'page_intro_text' => $this->intro_text ?: null,
            'page_cards' => $this->cleanItems($this->cards, ['title', 'text']) ?: null,
            'page_figures' => $this->cleanItems($this->figures, ['value', 'label']) ?: null,
            'page_cta_title' => $this->cta_title ?: null,
            'page_cta_text' => $this->cta_text ?: null,
            'page_cta_label' => $this->cta_label ?: null,
        ];

        if ($this->editingId) {
            $sector = Sector::findOrFail($this->editingId);
            $this->authorize('update', $sector);
            $sector->update($data);
        } else {
            $this->authorize('create', Sector::class);
            $sector = Sector::create($data);
        }

        if ($this->heroImage) {
            $sector->clearMediaCollection('hero');
            static::addMediaFromUpload($sector, $this->heroImage, 'hero');
        }

        $this->showForm = false;
        Sector::flushActiveListCache();
        $this->resetForm();
    }

    public function removeHeroImage(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $sector = Sector::findOrFail($this->editingId);
        $this->authorize('update', $sector);
        $sector->clearMediaCollection('hero');
        $this->heroImage = null;
    }

    public function addCard(): void
    {
        $this->cards[] = ['title' => '', 'text' => ''];
    }

    public function removeCard(int $index): void
    {
        unset($this->cards[$index]);
        $this->cards = array_values($this->cards);
    }

    public function moveCard(int $index, string $direction): void
    {
        $this->moveItem($this->cards, $index, $direction);
    }

    public function addFigure(): void
    {
        $this->figures[] = ['value' => '', 'label' => ''];
    }

    public function removeFigure(int $index): void
    {
        unset($this->figures[$index]);
        $this->figures = array_values($this->figures);
    }

    public function moveFigure(int $index, string $direction): void
    {
        $this->moveItem($this->figures, $index, $direction);
    }

    public function toggleActive(int $id): void
    {
        $sector = Sector::findOrFail($id);
        $this->authorize('update', $sector);
        $sector->update(['is_active' => !$sector->is_active]);
        Sector::flushActiveListCache();
    }

    public function delete(int $id): void
    {
        $sector = Sector::findOrFail($id);
        $this->authorize('delete', $sector);
        $sector->delete();
        Sector::flushActiveListCache();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'is_active', 'is_locked', 'sort_order', 'hero_title', 'hero_description', 'hero_cta_label', 'heroImage', 'intro_title', 'intro_text', 'cards', 'figures', 'cta_title', 'cta_text', 'cta_label']);

        $this->is_active = true;
        $this->is_locked = false;
        unset($this->editedSector);
    }

    /**
     * @param  array<int, array<string, string>>  $items
     * @param  array<int, string>  $keys
     * @return array<int, array<string, string>>
     */
    private function cleanItems(array $items, array $keys): array
    {
        return collect($items)
            ->map(function ($item) use ($keys) {
                $row = [];

                foreach ($keys as $key) {
                    $row[$key] = (string) ($item[$key] ?? '');
                }

                return $row;
            })
            ->filter(fn($row) => implode('', $row) !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, string>>  $items
     */
    private function moveItem(array &$items, int $index, string $direction): void
    {
        $target = $direction === 'up' ? $index - 1 : $index + 1;

        if (!isset($items[$index]) || !isset($items[$target])) {
            return;
        }

        [$items[$index], $items[$target]] = [$items[$target], $items[$index]];
        $items = array_values($items);
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Secteurs d’activité</flux:heading>
            <flux:subheading>Identité, hero et contenu des pages sectorielles, au même endroit.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Secteurs d’activité</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <x-admin.card title="Secteurs" :padded="false">

        <x-slot:actions>
            <flux:button variant="primary" size="sm" wire:click="create" icon="plus"/>
        </x-slot:actions>

        <x-admin.toolbar search-placeholder="Rechercher un secteur…">
            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </flux:select>
            </x-slot:filters>
        </x-admin.toolbar>

        <flux:table :paginate="$this->sectors">
        <flux:table.columns>
            <flux:table.column>Nom</flux:table.column>
                <flux:table.column>Slug</flux:table.column>
                <flux:table.column>Hero</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column>Ordre</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->sectors as $sector)
                    <flux:table.row wire:key="sector-row-{{ $sector->id }}">
                        <flux:table.cell variant="strong">{{ $sector->name }}</flux:table.cell>
                        <flux:table.cell>{{ $sector->slug }}</flux:table.cell>
                        <flux:table.cell>
                            @if ($hero = $sector->getFirstMediaUrl('hero', 'thumb'))
                                <img src="{{ $hero }}" alt="" class="h-10 w-16 rounded object-cover">
                            @else
                                <span class="text-xs text-zinc-400">Aucune</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-1">
                                @if ($sector->is_active)
                                    <flux:badge size="sm" color="green">Actif</flux:badge>
                                @else
                                    <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                                @endif
                                @if ($sector->is_locked)
                                    <flux:badge size="sm" color="zinc">Verrouillé</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $sector->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="filled"
                                    icon="{{ $sector->is_active ? 'eye-slash' : 'eye' }}"
                                    wire:click="toggleActive({{ $sector->id }})"
                                    aria-label="{{ $sector->is_active ? 'Désactiver' : 'Activer' }}"
                                    tooltip="{{ $sector->is_active ? 'Désactiver' : 'Activer' }}" />
                                <flux:button size="xs" variant="filled" icon="pencil"
                                    wire:click="edit({{ $sector->id }})" aria-label="Modifier" tooltip="Modifier" />
                                <flux:button size="xs" variant="filled" color="red" icon="trash"
                                    wire:click="delete({{ $sector->id }})" wire:confirm="Supprimer ce secteur ?"
                                    aria-label="Supprimer" tooltip="Supprimer" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>

    </x-admin.card>

    <flux:modal wire:model="showForm" class="md:w-[52rem]">
        <form wire:submit="save" class="space-y-5" x-data="{ tab: 'identite' }">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le secteur' : 'Nouveau secteur' }}
                </flux:heading>
                <flux:subheading>Les sections vides ne sont pas affichées sur le site public.</flux:subheading>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach (['identite' => 'Identité', 'hero' => 'Hero', 'contenu' => 'Contenu de page'] as $key => $label)
                    <button type="button" x-on:click="tab = '{{ $key }}'"
                        x-bind:class="tab === '{{ $key }}' ? 'border-cuivre bg-cuivre text-nuit' :
                            'border-zinc-200 text-zinc-600 hover:border-cuivre dark:border-zinc-700 dark:text-zinc-300'"
                        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors">{{ $label }}</button>
                @endforeach
            </div>

            <div x-show="tab === 'identite'" class="space-y-4">
                <flux:field>
                    <flux:label>Nom *</flux:label>
                    <flux:input wire:model="name" type="text" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Description courte</flux:label>
                    <flux:textarea wire:model="short_description" rows="2" />
                    <flux:error name="short_description" />
                </flux:field>

                <flux:field>
                    <flux:label>Description complète (SEO)</flux:label>
                    <flux:textarea wire:model="description" rows="4" />
                    <flux:description>Utilisée comme meta description de la page secteur (moteurs de recherche).
                        N’apparaît pas dans le corps de page.</flux:description>
                    <flux:error name="description" />
                </flux:field>

                <div class="grid sm:grid-cols-3 gap-4">
                    <flux:field>
                        <flux:label>Ordre</flux:label>
                        <flux:input wire:model="sort_order" type="number" min="0" />
                        <flux:error name="sort_order" />
                    </flux:field>

                    <flux:field variant="inline">
                        <flux:switch wire:model="is_active" />
                        <flux:label>Actif</flux:label>
                    </flux:field>

                    <flux:field variant="inline">
                        <flux:switch wire:model="is_locked" />
                        <flux:label>Verrouillé</flux:label>
                    </flux:field>
                </div>
            </div>

            <div x-show="tab === 'hero'" class="space-y-4">
                <div class="grid gap-4 sm:grid-cols-[16rem_1fr]">
                    <div class="space-y-2">
                        @if ($heroImage)
                            <img src="{{ $heroImage->temporaryUrl() }}" alt=""
                                class="h-32 w-full rounded object-cover">
                        @elseif($this->editedSector?->getFirstMediaUrl('hero', 'thumb'))
                            <img src="{{ $this->editedSector->getFirstMediaUrl('hero', 'thumb') }}" alt=""
                                class="h-32 w-full rounded object-cover">
                        @else
                            <div
                                class="flex h-32 w-full items-center justify-center rounded bg-nuit text-xs text-white/60">
                                Aucune image</div>
                        @endif
                        <input type="file" wire:model="heroImage" accept="image/*"
                            class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                        <flux:error name="heroImage" />
                        @if ($editingId)
                            <flux:button size="xs" variant="danger" wire:click="removeHeroImage">Retirer l’image
                            </flux:button>
                        @endif
                    </div>

                    <div class="space-y-4">
                        <flux:field>
                            <flux:label>Titre du hero</flux:label>
                            <flux:input wire:model="hero_title" type="text" />
                            <flux:error name="hero_title" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Description du hero</flux:label>
                            <flux:textarea wire:model="hero_description" rows="2" />
                            <flux:error name="hero_description" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Libellé du bouton du hero</flux:label>
                            <flux:input wire:model="hero_cta_label" type="text" />
                            <flux:error name="hero_cta_label" />
                        </flux:field>
                    </div>
                </div>
            </div>

            <div x-show="tab === 'contenu'" class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Titre d’introduction</flux:label>
                        <flux:input wire:model="intro_title" type="text" />
                        <flux:error name="intro_title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Libellé du bouton final</flux:label>
                        <flux:input wire:model="cta_label" type="text"
                            placeholder="Ex. Solliciter une étude technique" />
                        <flux:error name="cta_label" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Texte d’introduction</flux:label>
                    <flux:textarea wire:model="intro_text" rows="4" />
                    <flux:error name="intro_text" />
                </flux:field>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:label>Domaines d’expertise (cartes)</flux:label>
                        <flux:button size="sm" icon="plus" wire:click="addCard">Ajouter une carte
                        </flux:button>
                    </div>
                    @foreach ($cards as $index => $card)
                        <div wire:key="card-{{ $index }}"
                            class="grid gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-zinc-700">
                            <flux:input wire:model="cards.{{ $index }}.title" placeholder="Titre (ex. VRD)" />
                            <flux:input wire:model="cards.{{ $index }}.text" placeholder="Description" />
                            <div class="flex items-center gap-1">
                                <flux:button size="xs" icon="chevron-up"
                                    wire:click="moveCard({{ $index }}, 'up')" aria-label="Monter" />
                                <flux:button size="xs" icon="chevron-down"
                                    wire:click="moveCard({{ $index }}, 'down')" aria-label="Descendre" />
                                <flux:button size="xs" variant="danger" icon="trash"
                                    wire:click="removeCard({{ $index }})" aria-label="Supprimer" />
                            </div>
                        </div>
                    @endforeach
                    <flux:error name="cards" />
                </div>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <flux:label>Chiffres clés (max 4, laisser vide si non disponible)</flux:label>
                        <flux:button size="sm" icon="plus" wire:click="addFigure">Ajouter un chiffre
                        </flux:button>
                    </div>
                    @foreach ($figures as $index => $figure)
                        <div wire:key="figure-{{ $index }}"
                            class="grid gap-3 rounded-lg border border-zinc-200 p-3 sm:grid-cols-[1fr_2fr_auto] dark:border-zinc-700">
                            <flux:input wire:model="figures.{{ $index }}.value"
                                placeholder="Valeur (ex. 120)" />
                            <flux:input wire:model="figures.{{ $index }}.label"
                                placeholder="Libellé (ex. km de voies aménagées)" />
                            <div class="flex items-center gap-1">
                                <flux:button size="xs" icon="chevron-up"
                                    wire:click="moveFigure({{ $index }}, 'up')" aria-label="Monter" />
                                <flux:button size="xs" icon="chevron-down"
                                    wire:click="moveFigure({{ $index }}, 'down')" aria-label="Descendre" />
                                <flux:button size="xs" variant="danger" icon="trash"
                                    wire:click="removeFigure({{ $index }})" aria-label="Supprimer" />
                            </div>
                        </div>
                    @endforeach
                    <flux:error name="figures" />
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Titre de l’appel à l’action</flux:label>
                        <flux:input wire:model="cta_title" type="text" />
                        <flux:error name="cta_title" />
                    </flux:field>
                    <flux:field>
                        <flux:label>Texte de l’appel à l’action</flux:label>
                        <flux:input wire:model="cta_text" type="text" />
                        <flux:error name="cta_text" />
                    </flux:field>
                </div>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>

</div>
