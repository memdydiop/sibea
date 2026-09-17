<?php

use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Rôles')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    /** @var array<string> */
    public array $permission_names = [];

    public function mount(): void
    {
        $this->authorize('viewAny', Role::class);
    }

    #[Computed]
    public function roles()
    {
        return Role::query()
            ->withCount(['permissions', 'users'])
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function permissions()
    {
        return Permission::orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', Role::class);
        $this->reset(['editingId', 'name', 'permission_names']);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);
        $this->editingId = $role->id;
        $this->name = $role->name;
        $this->permission_names = $role->permissions->pluck('name')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->editingId)],
            'permission_names' => ['array'],
            'permission_names.*' => ['exists:permissions,name'],
        ]);

        if ($this->editingId) {
            $role = Role::findOrFail($this->editingId);
            $this->authorize('update', $role);
            $role->update(['name' => $validated['name']]);
        } else {
            $this->authorize('create', Role::class);
            $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);
        }

        $role->syncPermissions($validated['permission_names'] ?? []);

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'permission_names']);
    }

    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);
        $role->delete();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Rôles</flux:heading>
            <flux:subheading>Rôles d’accès et leurs permissions.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouveau rôle</flux:button>
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input wire:model.live="search" type="search" placeholder="Rechercher un rôle…" />
    </div>

    <flux:table :paginate="$this->roles">
        <flux:table.columns>
            <flux:table.column>Nom</flux:table.column>
            <flux:table.column>Permissions</flux:table.column>
            <flux:table.column>Utilisateurs</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->roles as $role)
                <flux:table.row wire:key="role-row-{{ $role->id }}">
                    <flux:table.cell variant="strong">{{ $role->name }}</flux:table.cell>
                    <flux:table.cell>{{ $role->permissions_count }}</flux:table.cell>
                    <flux:table.cell>{{ $role->users_count }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit({{ $role->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $role->id }})" wire:confirm="Supprimer ce rôle ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier le rôle' : 'Nouveau rôle' }}</flux:heading>
                <flux:subheading>Nom et permissions associées.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nom *</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Permissions</flux:label>
                <div class="grid gap-2 mt-1">
                    @foreach($this->permissions as $permission)
                        <label class="flex items-center gap-2 text-sm">
                            <flux:checkbox wire:model="permission_names" :value="$permission->name" />
                            {{ $permission->name }}
                        </label>
                    @endforeach
                </div>
                <flux:error name="permission_names" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
