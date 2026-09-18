<?php

use App\Models\Sector;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Secteurs')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $slug = '';

    public ?string $short_description = null;

    public ?string $description = null;

    public bool $is_active = true;

    public bool $is_locked = false;

    public int $sort_order = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Sector::class);
    }

    #[Computed]
    public function sectors()
    {
        return Sector::query()
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->ordered()
            ->paginate(10);
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
        $this->authorize('create', Sector::class);
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'is_active', 'is_locked', 'sort_order']);
        $this->is_active = true;
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
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('sectors', 'slug')->ignore($this->editingId)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'is_locked' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        if ($this->editingId) {
            $sector = Sector::findOrFail($this->editingId);
            $this->authorize('update', $sector);
            $sector->update($validated);
        } else {
            $this->authorize('create', Sector::class);
            Sector::create($validated);
        }

        $this->showForm = false;
        Sector::flushActiveListCache();
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'sort_order']);
        $this->is_active = true;
        $this->is_locked = false;
    }

    public function toggleActive(int $id): void
    {
        $sector = Sector::findOrFail($id);
        $this->authorize('update', $sector);
        $sector->update(['is_active' => ! $sector->is_active]);
        Sector::flushActiveListCache();
    }

    public function delete(int $id): void
    {
        $sector = Sector::findOrFail($id);
        $this->authorize('delete', $sector);
        $sector->delete();
        Sector::flushActiveListCache();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Secteurs d’activité</flux:heading>
            <flux:subheading>Gérez les 4 secteurs officiels et leurs contenus hero.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouveau secteur</flux:button>
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input wire:model.live="search" type="search" placeholder="Rechercher un secteur…" />
    </div>

    <flux:table :paginate="$this->sectors">
        <flux:table.columns>
            <flux:table.column>Nom</flux:table.column>
            <flux:table.column>Slug</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column>Ordre</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->sectors as $sector)
                <flux:table.row wire:key="sector-row-{{ $sector->id }}">
                    <flux:table.cell variant="strong">{{ $sector->name }}</flux:table.cell>
                    <flux:table.cell>{{ $sector->slug }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex gap-1">
                            @if($sector->is_active)
                                <flux:badge size="sm" color="green">Actif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                            @endif
                            @if($sector->is_locked)
                                <flux:badge size="sm" color="zinc">Verrouillé</flux:badge>
                            @endif
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $sector->sort_order }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="toggleActive({{ $sector->id }})">
                                {{ $sector->is_active ? 'Désactiver' : 'Activer' }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $sector->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $sector->id }})" wire:confirm="Supprimer ce secteur ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le secteur' : 'Nouveau secteur' }}</flux:heading>
                <flux:subheading>Contenus affichés sur le site public et le carrousel.</flux:subheading>
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

            <flux:callout icon="information-circle">
                Le hero de la page secteur (image, titre, description, bouton) se règle dans
                <strong>Paramètres → Pages</strong>.
            </flux:callout>

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

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
