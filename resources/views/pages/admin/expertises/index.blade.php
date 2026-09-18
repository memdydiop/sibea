<?php

use App\Concerns\AddsMediaFromUploads;
use App\Models\Expertise;
use App\Models\Sector;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public bool $showForm = false;

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

    public $icon = null;

    public ?string $coverPreview = null;

    public function mount(): void
    {
        $this->authorize('viewAny', Expertise::class);
    }

    #[Computed]
    public function expertises()
    {
        return Expertise::query()
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->ordered()
            ->paginate(10);
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

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedName(): void
    {
        if ($this->editingId === null) {
            $this->slug = Str::slug($this->name);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Expertise::class);
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'benefits_text', 'process_text', 'sort_order', 'sector_ids', 'service_ids', 'cover', 'icon', 'coverPreview']);
        $this->is_active = true;
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
        $this->reset(['cover', 'icon']);
        $this->showForm = true;
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

    public function save(): void
    {
        $validated = $this->validate([
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
            'icon' => ['nullable', 'image', 'max:5120'],
        ]);

        $data = $validated;
        $data['benefits'] = $this->linesToArray($validated['benefits_text'] ?? null) ?: null;
        $data['process_steps'] = $this->linesToArray($validated['process_text'] ?? null) ?: null;
        unset($data['benefits_text'], $data['process_text'], $data['cover'], $data['icon'], $data['sector_ids'], $data['service_ids']);

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
        if ($this->icon) {
            $expertise->clearMediaCollection('icon');
            static::addMediaFromUpload($expertise, $this->icon, 'icon');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'benefits_text', 'process_text', 'sort_order', 'sector_ids', 'service_ids', 'cover', 'icon', 'coverPreview']);
        $this->is_active = true;
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
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Expertises</flux:heading>
            <flux:subheading>Savoir-faire, visuels et associations secteurs / services.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouvelle expertise</flux:button>
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input wire:model.live="search" type="search" placeholder="Rechercher une expertise…" />
    </div>

    <flux:table :paginate="$this->expertises">
        <flux:table.columns>
            <flux:table.column>Visuel</flux:table.column>
            <flux:table.column>Nom</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->expertises as $expertise)
                <flux:table.row wire:key="expertise-row-{{ $expertise->id }}">
                    <flux:table.cell>
                        @if($expertise->getFirstMediaUrl('cover'))
                            <img src="{{ $expertise->getFirstMediaUrl('cover') }}" alt="" class="h-10 w-16 object-cover rounded">
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell variant="strong">{{ $expertise->name }}</flux:table.cell>
                    <flux:table.cell>
                        @if($expertise->is_active)
                            <flux:badge size="sm" color="green">Actif</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="toggleActive({{ $expertise->id }})">
                                {{ $expertise->is_active ? 'Désactiver' : 'Activer' }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $expertise->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $expertise->id }})" wire:confirm="Supprimer cette expertise ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[44rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier l’expertise' : 'Nouvelle expertise' }}</flux:heading>
                <flux:subheading>Contenus, visuels (max 5 Mo) et associations.</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Nom *</flux:label>
                    <flux:input wire:model="name" type="text" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Slug *</flux:label>
                    <flux:input wire:model="slug" type="text" />
                    <flux:error name="slug" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Description courte</flux:label>
                <flux:textarea wire:model="short_description" rows="2" />
                <flux:error name="short_description" />
            </flux:field>

            <flux:field>
                <flux:label>Description complète</flux:label>
                <flux:textarea wire:model="description" rows="4" />
                <flux:error name="description" />
            </flux:field>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Bénéfices (un par ligne)</flux:label>
                    <flux:textarea wire:model="benefits_text" rows="3" />
                    <flux:error name="benefits_text" />
                </flux:field>

                <flux:field>
                    <flux:label>Étapes d’intervention (une par ligne)</flux:label>
                    <flux:textarea wire:model="process_text" rows="3" />
                    <flux:error name="process_text" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Image de couverture (max 5 Mo)</flux:label>
                    @if($coverPreview)
                        <img src="{{ $coverPreview }}" alt="" class="h-16 w-24 object-cover rounded mb-2">
                    @endif
                    <input type="file" wire:model="cover" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                    <flux:error name="cover" />
                </flux:field>

                <flux:field>
                    <flux:label>Icône (max 5 Mo)</flux:label>
                    <input type="file" wire:model="icon" accept="image/*" class="block w-full text-sm border border-zinc-200 dark:border-zinc-700 rounded-lg p-2">
                    <flux:error name="icon" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Secteurs concernés</flux:label>
                    <div class="grid gap-2 mt-1">
                        @foreach($this->sectors as $sector)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="sector_ids" :value="$sector->id" />
                                {{ $sector->name }}
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="sector_ids" />
                </flux:field>

                <flux:field>
                    <flux:label>Services associés</flux:label>
                    <div class="grid gap-2 mt-1">
                        @foreach($this->services as $service)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="service_ids" :value="$service->id" />
                                {{ $service->name }}
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="service_ids" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Ordre</flux:label>
                    <flux:input wire:model="sort_order" type="number" min="0" />
                    <flux:error name="sort_order" />
                </flux:field>

                <flux:field variant="inline">
                    <flux:switch wire:model="is_active" />
                    <flux:label>Actif</flux:label>
                </flux:field>
            </div>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
