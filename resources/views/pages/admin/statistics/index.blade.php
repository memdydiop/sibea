<?php

use App\Models\Statistic;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Chiffres clés')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public int $perPage = 10;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $key = '';

    public string $label = '';

    public string $value = '';

    public bool $is_active = false;

    public int $sort_order = 0;

    public function mount(): void
    {
        $this->authorize('viewAny', Statistic::class);
    }

    #[Computed]
    public function statistics()
    {
        return Statistic::query()
            ->when($this->search, fn ($query) => $query->where(fn ($subQuery) => $subQuery
                ->whereRaw('LOWER(label) LIKE ?', ['%'.mb_strtolower($this->search).'%'])
                ->orWhereRaw('LOWER(key) LIKE ?', ['%'.mb_strtolower($this->search).'%'])))
            ->when($this->statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($this->statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->ordered()
            ->paginate($this->perPage);
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

    public function updatedLabel(): void
    {
        if ($this->editingId === null) {
            $this->key = Str::slug($this->label);
        }
    }

    public function create(): void
    {
        $this->authorize('create', Statistic::class);
        $this->reset(['editingId', 'key', 'label', 'value', 'sort_order']);
        $this->is_active = false;
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $statistic = Statistic::findOrFail($id);
        $this->authorize('update', $statistic);
        $this->editingId = $statistic->id;
        $this->key = $statistic->key;
        $this->label = $statistic->label;
        $this->value = $statistic->value;
        $this->is_active = $statistic->is_active;
        $this->sort_order = $statistic->sort_order;
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'key' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('statistics', 'key')->ignore($this->editingId)],
            'label' => ['required', 'string', 'max:255'],
            'value' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        if ($this->editingId) {
            $statistic = Statistic::findOrFail($this->editingId);
            $this->authorize('update', $statistic);
            $statistic->update($validated);
        } else {
            $this->authorize('create', Statistic::class);
            Statistic::create($validated);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'key', 'label', 'value', 'sort_order']);
        $this->is_active = false;
    }

    public function toggleActive(int $id): void
    {
        $statistic = Statistic::findOrFail($id);
        $this->authorize('update', $statistic);
        $statistic->update(['is_active' => ! $statistic->is_active]);
    }

    public function delete(int $id): void
    {
        $statistic = Statistic::findOrFail($id);
        $this->authorize('delete', $statistic);
        $statistic->delete();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Chiffres clés</flux:heading>
            <flux:subheading>Indicateurs affichés dans la bande de statistiques de l’accueil.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Chiffres clés</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <x-admin.card title="Chiffres clés" :padded="false">

        <x-slot:actions>
            <flux:button variant="primary" size="sm" wire:click="create" icon="plus" aria-label="Nouveau chiffre" tooltip="Nouveau chiffre" />
        </x-slot:actions>

        <x-admin.toolbar search-placeholder="Rechercher un chiffre clé…">
            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="inactive">Inactif</option>
                </flux:select>
            </x-slot:filters>
        </x-admin.toolbar>

        <div class="overflow-x-auto">
            <flux:table :paginate="$this->statistics">
            <flux:table.columns>
                <flux:table.column>Valeur</flux:table.column>
                <flux:table.column>Libellé</flux:table.column>
                <flux:table.column>Clé</flux:table.column>
                <flux:table.column>Ordre</flux:table.column>
                <flux:table.column>Statut</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach($this->statistics as $statistic)
                    <flux:table.row wire:key="statistic-row-{{ $statistic->id }}">
                        <flux:table.cell variant="strong">{{ $statistic->value }}</flux:table.cell>
                        <flux:table.cell>{{ $statistic->label }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:badge size="sm" color="zinc">{{ $statistic->key }}</flux:badge>
                        </flux:table.cell>
                        <flux:table.cell>{{ $statistic->sort_order }}</flux:table.cell>
                        <flux:table.cell>
                            @if($statistic->is_active)
                                <flux:badge size="sm" color="green">Actif</flux:badge>
                            @else
                                <flux:badge size="sm" color="zinc">Inactif</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                <flux:button size="xs" variant="filled"
                                    icon="{{ $statistic->is_active ? 'eye-slash' : 'eye' }}"
                                    wire:click="toggleActive({{ $statistic->id }})"
                                    aria-label="{{ $statistic->is_active ? 'Désactiver' : 'Activer' }}"
                                    tooltip="{{ $statistic->is_active ? 'Désactiver' : 'Activer' }}" />
                                <flux:button size="xs" variant="filled" icon="pencil"
                                    wire:click="edit({{ $statistic->id }})" aria-label="Modifier" tooltip="Modifier" />
                                <flux:button size="xs" variant="filled" color="red" icon="trash"
                                    wire:click="delete({{ $statistic->id }})" wire:confirm="Supprimer ce chiffre clé ?"
                                    aria-label="Supprimer" tooltip="Supprimer" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
            </flux:table>
        </div>

    </x-admin.card>

    <flux:modal wire:model="showForm" class="md:w-[36rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le chiffre clé' : 'Nouveau chiffre clé' }}</flux:heading>
                <flux:subheading>Affiché sur l’accueil s’il est actif.</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Valeur *</flux:label>
                    <flux:input wire:model="value" type="text" placeholder="Ex. 250" />
                    <flux:error name="value" />
                </flux:field>

                <flux:field>
                    <flux:label>Libellé *</flux:label>
                    <flux:input wire:model.live.debounce.400ms="label" type="text" placeholder="Ex. Projets livrés" />
                    <flux:error name="label" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Clé technique *</flux:label>
                <flux:input wire:model="key" type="text" placeholder="projets-livres" />
                <flux:description>Identifiant unique utilisé par la commande `statistics:toggle`.</flux:description>
                <flux:error name="key" />
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
