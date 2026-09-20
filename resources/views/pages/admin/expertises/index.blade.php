<?php

use App\Concerns\AddsMediaFromUploads;
use App\Models\Expertise;
use App\Models\Sector;
use App\Models\Service;
use App\Support\UniqueSlug;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Expertises')] class extends Component
{
    use AddsMediaFromUploads, WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 10;

    public bool $showForm = false;

    public string $tab = 'contenu';

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    public ?string $benefits_text = null;

    public ?string $process_text = null;

    public bool $is_active = true;

    public int $sort_order = 0;

    /** @var array<int> */
    public array $sector_ids = [];

    /** @var array<int> */
    public array $service_ids = [];

    public $cover = null;

    public ?string $coverPreview = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Expertise::class);
    }

    #[Computed]
    public function expertises()
    {
        return Expertise::query()
            ->with(['sectors', 'media'])
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->ordered()
            ->paginate($this->perPage);
    }

    #[Computed]
    public function sectors()
    {
        return Sector::active()->ordered()->get();
    }

    #[Computed]
    public function services()
    {
        return Service::active()->ordered()->get();
    }

    #[Computed]
    public function editedExpertise(): ?Expertise
    {
        return $this->editingId ? Expertise::find($this->editingId) : null;
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

        if (! $isAutomaticSlug) {
            $expertise = Expertise::find($this->editingId);
            $isAutomaticSlug = $expertise !== null
                && $this->slug === $expertise->slug
                && $expertise->slug === Str::slug($expertise->name);
        }

        if ($isAutomaticSlug) {
            $this->slug = UniqueSlug::for(Expertise::class, $this->name, $this->editingId);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Expertise::class);
        $this->resetForm();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $expertise = Expertise::with(['sectors', 'services'])->findOrFail($id);
        $this->authorize('update', $expertise);

        $this->editingId = $expertise->id;
        $this->name = $expertise->name;
        $this->slug = $expertise->slug;
        $this->short_description = $expertise->short_description;
        $this->description = $expertise->description;
        $this->benefits_text = $expertise->benefits ? implode("\n", $expertise->benefits) : null;
        $this->process_text = $expertise->process_steps ? implode("\n", $expertise->process_steps) : null;
        $this->is_active = $expertise->is_active;
        $this->sort_order = $expertise->sort_order;
        $this->sector_ids = $expertise->sectors->pluck('id')->all();
        $this->service_ids = $expertise->services->pluck('id')->all();
        $this->coverPreview = $expertise->getFirstMediaUrl('cover') ?: null;
        $this->reset(['cover']);
        $this->showForm = true;
    }

    public function save(): void
    {
        if (trim($this->slug) === '') {
            $this->slug = UniqueSlug::for(Expertise::class, $this->name, $this->editingId);
        }

        $validated = $this->validatedData();

        $data = $validated;
        $data['benefits'] = $this->linesToArray($validated['benefits_text'] ?? null) ?: null;
        $data['process_steps'] = $this->linesToArray($validated['process_text'] ?? null) ?: null;
        unset($data['benefits_text'], $data['process_text'], $data['cover'], $data['sector_ids'], $data['service_ids']);

        if ($this->editingId) {
            $expertise = Expertise::findOrFail($this->editingId);
            $this->authorize('update', $expertise);
            $expertise->update($data);
        } else {
            $this->authorize('create', Expertise::class);
            $expertise = Expertise::create($data);
        }

        $expertise->sectors()->sync($validated['sector_ids'] ?? []);
        $expertise->services()->sync($validated['service_ids'] ?? []);

        if ($this->cover) {
            $expertise->clearMediaCollection('cover');
            static::addMediaFromUpload($expertise, $this->cover, 'cover');
        }

        $this->showForm = false;
        $this->resetForm();
    }

    public function removeCover(): void
    {
        if ($this->editingId === null) {
            return;
        }

        $expertise = Expertise::findOrFail($this->editingId);
        $this->authorize('update', $expertise);
        $expertise->clearMediaCollection('cover');
        $this->cover = null;
        $this->coverPreview = null;
    }

    public function toggleActive(int $id): void
    {
        $expertise = Expertise::findOrFail($id);
        $this->authorize('update', $expertise);
        $expertise->update(['is_active' => ! $expertise->is_active]);
    }

    public function delete(int $id): void
    {
        $expertise = Expertise::findOrFail($id);
        $this->authorize('delete', $expertise);
        $expertise->delete();
    }

    /**
     * @var array<string, array<int, string>>
     */
    private const TAB_FIELDS = [
        'contenu' => ['name', 'slug', 'short_description', 'description', 'benefits_text', 'process_text'],
        'visuels' => ['cover'],
        'associations' => ['sector_ids', 'service_ids', 'sort_order', 'is_active'],
    ];

    /**
     * @return array<string, mixed>
     */
    private function validatedData(): array
    {
        try {
            return $this->validate([
                'name' => ['required', 'string', 'max:255'],
                'slug' => ['required', 'string', 'max:255', Rule::unique('expertises', 'slug')->ignore($this->editingId)],
                'short_description' => ['nullable', 'string'],
                'description' => ['nullable', 'string'],
                'benefits_text' => ['nullable', 'string'],
                'process_text' => ['nullable', 'string'],
                'is_active' => ['boolean'],
                'sort_order' => ['integer', 'min:0'],
                'sector_ids' => ['array'],
                'sector_ids.*' => ['exists:sectors,id'],
                'service_ids' => ['array'],
                'service_ids.*' => ['exists:services,id'],
                'cover' => ['nullable', 'image', 'max:5120'],
            ]);
        } catch (ValidationException $exception) {
            $this->tab = $this->tabForErrors($exception->validator->errors()->keys());

            throw $exception;
        }
    }

    /**
     * @param  array<int, string>  $fields
     */
    private function tabForErrors(array $fields): string
    {
        foreach (self::TAB_FIELDS as $tab => $tabFields) {
            if (array_intersect($fields, $tabFields) !== []) {
                return $tab;
            }
        }

        return 'contenu';
    }

    public function tabHasErrors(string $tab): bool
    {
        foreach (self::TAB_FIELDS[$tab] ?? [] as $field) {
            if ($this->getErrorBag()->has($field)) {
                return true;
            }
        }

        return false;
    }

    private function resetForm(): void
    {
        $this->reset([
            'editingId', 'name', 'slug', 'short_description', 'description',
            'benefits_text', 'process_text', 'sort_order', 'sector_ids', 'service_ids',
            'cover', 'coverPreview',
        ]);

        $this->tab = 'contenu';
        $this->is_active = true;
        unset($this->editedExpertise);
    }

    /**
     * @return array<int, string>
     */
    private function linesToArray(?string $text): array
    {
        return collect(preg_split('/\r?\n/', $text ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Expertises</flux:heading>
            <flux:subheading>Savoir-faire, visuels et associations secteurs / services.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Expertises</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <x-admin.card title="Expertises" :padded="false">

        <x-slot:actions>
            <flux:button variant="primary" size="sm" wire:click="create" icon="plus" aria-label="Nouvelle expertise" tooltip="Nouvelle expertise" />
        </x-slot:actions>

        <x-admin.toolbar search-placeholder="Rechercher une expertise…">
            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </flux:select>
            </x-slot:filters>
        </x-admin.toolbar>

        <div class="overflow-x-auto">
            <flux:table :paginate="$this->expertises">
            <flux:table.columns>
                <flux:table.column>Visuel</flux:table.column>
                <flux:table.column>Nom</flux:table.column>
                <flux:table.column>Secteurs</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->expertises as $expertise)
                    <flux:table.row wire:key="expertise-row-{{ $expertise->id }}">
                        <flux:table.cell>
                            @if($expertise->getFirstMediaUrl('cover', 'thumb'))
                                <img src="{{ $expertise->getFirstMediaUrl('cover', 'thumb') }}" alt="" class="h-10 w-16 object-cover rounded">
                            @else
                                <span class="text-zinc-400">—</span>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell variant="strong">{{ $expertise->name }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @forelse($expertise->sectors as $sector)
                                    <flux:badge size="sm">{{ $sector->name }}</flux:badge>
                                @empty
                                    <span class="text-xs text-zinc-400">—</span>
                                @endforelse
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($expertise->is_active)
                                <flux:badge size="sm" color="green">Actif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="filled" icon="{{ $expertise->is_active ? 'eye-slash' : 'eye' }}" wire:click="toggleActive({{ $expertise->id }})" aria-label="{{ $expertise->is_active ? 'Désactiver' : 'Activer' }}" tooltip="{{ $expertise->is_active ? 'Désactiver' : 'Activer' }}" />
                                <flux:button size="xs" variant="filled" icon="pencil" wire:click="edit({{ $expertise->id }})" aria-label="Modifier" tooltip="Modifier" />
                                <flux:button size="xs" variant="filled" color="red" icon="trash" wire:click="delete({{ $expertise->id }})" wire:confirm="Supprimer cette expertise ?" aria-label="Supprimer" tooltip="Supprimer" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
            </flux:table>
        </div>

    </x-admin.card>

    <flux:modal wire:model="showForm" class="md:w-[48rem]">
        <form wire:submit="save" class="space-y-5" wire:key="expertise-form-{{ $tab }}" x-data="{ tab: @js($tab) }">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier l’expertise' : 'Nouvelle expertise' }}</flux:heading>
                <flux:subheading>Contenus, visuels (max 5 Mo) et associations.</flux:subheading>
            </div>

            <div class="flex flex-wrap gap-2">
                @foreach(['contenu' => 'Contenu', 'visuels' => 'Visuels', 'associations' => 'Associations'] as $key => $label)
                    <button
                        type="button"
                        x-on:click="tab = '{{ $key }}'"
                        x-bind:class="tab === '{{ $key }}' ? 'border-cuivre bg-cuivre text-nuit' : 'border-zinc-200 text-zinc-600 hover:border-cuivre dark:border-zinc-700 dark:text-zinc-300'"
                        class="rounded-full border px-4 py-1.5 text-sm font-semibold transition-colors"
                    >
                        {{ $label }}
                        @if($this->tabHasErrors($key))
                            <span class="ml-1 inline-block size-2 rounded-full bg-red-500 align-middle" title="Champs à corriger"></span>
                        @endif
                    </button>
                @endforeach
            </div>

            <div x-show="tab === 'contenu'" class="space-y-4">
                <flux:field>
                    <flux:label>Nom *</flux:label>
                    <flux:input wire:model="name" type="text" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Description courte</flux:label>
                    <flux:textarea wire:model="short_description" rows="2" />
                    <flux:description>Affichée sur les cartes et les listes.</flux:description>
                    <flux:error name="short_description" />
                </flux:field>

                <flux:field>
                    <flux:label>Description complète</flux:label>
                    <flux:textarea wire:model="description" rows="4" />
                    <flux:error name="description" />
                </flux:field>

                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Bénéfices</flux:label>
                        <flux:textarea wire:model="benefits_text" rows="4" />
                        <flux:description>Un bénéfice par ligne.</flux:description>
                        <flux:error name="benefits_text" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Étapes d’intervention</flux:label>
                        <flux:textarea wire:model="process_text" rows="4" />
                        <flux:description>Une étape par ligne, dans l’ordre.</flux:description>
                        <flux:error name="process_text" />
                    </flux:field>
                </div>
            </div>

            <div x-show="tab === 'visuels'" class="space-y-4">
                <flux:field>
                    <flux:label>Image de couverture (max 5 Mo)</flux:label>
                    <div class="space-y-2">
                        @if($cover)
                            <img src="{{ $cover->temporaryUrl() }}" alt="" class="h-24 w-36 rounded object-cover">
                        @elseif($coverPreview)
                            <img src="{{ $coverPreview }}" alt="" class="h-24 w-36 rounded object-cover">
                        @else
                            <div class="flex h-24 w-36 items-center justify-center rounded bg-zinc-100 text-xs text-zinc-400 dark:bg-zinc-800">Aucune image</div>
                        @endif
                        <input type="file" wire:model="cover" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                        <flux:error name="cover" />
                        @if($editingId && ($coverPreview || $cover))
                            <flux:button size="xs" variant="danger" wire:click="removeCover">Retirer l’image</flux:button>
                        @endif
                    </div>
                </flux:field>
                <flux:callout icon="information-circle" class="text-sm">
                    Les images sont converties en WebP automatiquement à l’enregistrement.
                </flux:callout>
            </div>

            <div x-show="tab === 'associations'" class="space-y-4">
                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Secteurs concernés</flux:label>
                        <div class="grid gap-2 mt-1">
                            @forelse($this->sectors as $sector)
                                <label class="flex items-center gap-2 text-sm">
                                    <flux:checkbox wire:model="sector_ids" :value="$sector->id" />
                                    {{ $sector->name }}
                                </label>
                            @empty
                                <span class="text-sm text-zinc-400">Aucun secteur actif.</span>
                            @endforelse
                        </div>
                        <flux:error name="sector_ids" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Services associés</flux:label>
                        <div class="grid gap-2 mt-1">
                            @forelse($this->services as $service)
                                <label class="flex items-center gap-2 text-sm">
                                    <flux:checkbox wire:model="service_ids" :value="$service->id" />
                                    {{ $service->name }}
                                </label>
                            @empty
                                <span class="text-sm text-zinc-400">Aucun service actif.</span>
                            @endforelse
                        </div>
                        <flux:error name="service_ids" />
                    </flux:field>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Ordre</flux:label>
                        <flux:input wire:model="sort_order" type="number" min="0" />
                        <flux:description>Plus petit = affiché en premier.</flux:description>
                        <flux:error name="sort_order" />
                    </flux:field>

                    <flux:field variant="inline">
                        <flux:switch wire:model="is_active" />
                        <flux:label>Actif</flux:label>
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span wire:loading wire:target="cover" class="text-xs font-medium text-cuivre">Téléversement en cours…</span>
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled" wire:target="cover,save">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
