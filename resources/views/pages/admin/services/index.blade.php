<?php

use App\Models\Expertise;
use App\Models\Service;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Services')] class extends Component
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

    public int $sort_order = 0;

    /** @var array<int> */
    public array $expertise_ids = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Service::class);
    }

    #[Computed]
    public function services()
    {
        return Service::query()
            ->with('expertises')
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->ordered()
            ->paginate(10);
    }

    #[Computed]
    public function expertises()
    {
        return Expertise::active()->ordered()->get();
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
        $this->authorize('create', Service::class);
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'sort_order', 'expertise_ids']);
        $this->is_active = true;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $service = Service::with('expertises')->findOrFail($id);
        $this->authorize('update', $service);
        $this->editingId = $service->id;
        $this->name = $service->name;
        $this->slug = $service->slug;
        $this->short_description = $service->short_description;
        $this->description = $service->description;
        $this->is_active = $service->is_active;
        $this->sort_order = $service->sort_order;
        $this->expertise_ids = $service->expertises->pluck('id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', Rule::unique('services', 'slug')->ignore($this->editingId)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'expertise_ids' => ['array'],
            'expertise_ids.*' => ['exists:expertises,id'],
        ]);

        if ($this->editingId) {
            $service = Service::findOrFail($this->editingId);
            $this->authorize('update', $service);
            $service->update($validated);
            $service->expertises()->sync($validated['expertise_ids'] ?? []);
        } else {
            $this->authorize('create', Service::class);
            $service = Service::create($validated);
            $service->expertises()->sync($validated['expertise_ids'] ?? []);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'slug', 'short_description', 'description', 'sort_order', 'expertise_ids']);
        $this->is_active = true;
    }

    public function toggleActive(int $id): void
    {
        $service = Service::findOrFail($id);
        $this->authorize('update', $service);
        $service->update(['is_active' => ! $service->is_active]);
    }

    public function delete(int $id): void
    {
        $service = Service::findOrFail($id);
        $this->authorize('delete', $service);
        $service->delete();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Services</flux:heading>
            <flux:subheading>Prestations concrètes rattachées aux expertises.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouveau service</flux:button>
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input wire:model.live="search" type="search" placeholder="Rechercher un service…" />
    </div>

    <flux:table :paginate="$this->services">
        <flux:table.columns>
            <flux:table.column>Nom</flux:table.column>
            <flux:table.column>Expertises</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->services as $service)
                <flux:table.row wire:key="service-row-{{ $service->id }}">
                    <flux:table.cell variant="strong">{{ $service->name }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @foreach($service->expertises as $expertise)
                                <flux:badge size="sm" color="zinc">{{ $expertise->name }}</flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        @if($service->is_active)
                            <flux:badge size="sm" color="green">Actif</flux:badge>
                        @else
                            <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                        @endif
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="toggleActive({{ $service->id }})">
                                {{ $service->is_active ? 'Désactiver' : 'Activer' }}
                            </flux:button>
                            <flux:button size="sm" wire:click="edit({{ $service->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $service->id }})" wire:confirm="Supprimer ce service ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le service' : 'Nouveau service' }}</flux:heading>
                <flux:subheading>Rattachez le service à une ou plusieurs expertises.</flux:subheading>
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

            <flux:field>
                <flux:label>Expertises associées</flux:label>
                <div class="grid sm:grid-cols-2 gap-2 mt-1">
                    @foreach($this->expertises as $expertise)
                        <label class="flex items-center gap-2 text-sm">
                            <flux:checkbox wire:model="expertise_ids" :value="$expertise->id" />
                            {{ $expertise->name }}
                        </label>
                    @endforeach
                </div>
                <flux:error name="expertise_ids" />
            </flux:field>

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
