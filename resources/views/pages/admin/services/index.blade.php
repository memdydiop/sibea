<?php

use App\Support\UniqueSlug;
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

    public string $statusFilter = '';

    public int $perPage = 10;

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
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->ordered()
            ->paginate($this->perPage);
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
            $service = Service::find($this->editingId);
            $isAutomaticSlug = $service !== null
                && $this->slug === $service->slug
                && $service->slug === Str::slug($service->name);
        }

        if ($isAutomaticSlug) {
            $this->slug = UniqueSlug::for(Service::class, $this->name, $this->editingId);
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
        if (trim($this->slug) === '') {
            $this->slug = UniqueSlug::for(Service::class, $this->name, $this->editingId);
        }

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
            <flux:heading size="xl" class="mb-0!">Services</flux:heading>
            <flux:subheading>Prestations concrètes rattachées aux expertises.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Services</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <x-admin.card title="Services" :padded="false">

        <x-slot:actions>
            <flux:button variant="primary" size="sm" wire:click="create" icon="plus" aria-label="Nouveau service" tooltip="Nouveau service" />
        </x-slot:actions>

        <x-admin.toolbar search-placeholder="Rechercher un service…">
            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </flux:select>
            </x-slot:filters>
        </x-admin.toolbar>

        <div class="overflow-x-auto">
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
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="filled" icon="{{ $service->is_active ? 'eye-slash' : 'eye' }}" wire:click="toggleActive({{ $service->id }})" aria-label="{{ $service->is_active ? 'Désactiver' : 'Activer' }}" tooltip="{{ $service->is_active ? 'Désactiver' : 'Activer' }}" />
                                <flux:button size="xs" variant="filled" icon="pencil" wire:click="edit({{ $service->id }})" aria-label="Modifier" tooltip="Modifier" />
                                <flux:button size="xs" variant="filled" color="red" icon="trash" wire:click="delete({{ $service->id }})" wire:confirm="Supprimer ce service ?" aria-label="Supprimer" tooltip="Supprimer" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
            </flux:table>
        </div>

    </x-admin.card>

    <flux:modal wire:model="showForm" class="md:w-[40rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le service' : 'Nouveau service' }}</flux:heading>
                <flux:subheading>Rattachez le service à une ou plusieurs expertises.</flux:subheading>
            </div>

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
