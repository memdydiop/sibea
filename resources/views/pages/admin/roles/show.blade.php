<?php

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Détail du rôle')] class extends Component
{
    use WithPagination;

    public Role $role;

    public bool $showForm = false;

    public string $name = '';

    public ?string $description = null;

    /** @var array<string> */
    public array $permission_names = [];

    /** @var array<int> */
    public array $user_ids = [];

    public string $search = '';

    public int $perPage = 10;

    public function mount(Role $role): void
    {
        $this->authorize('view', $role);

        $this->role = $role;
    }

    #[Computed]
    public function permissions()
    {
        return $this->role->permissions()
            ->when($this->search, fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%']))
            ->orderBy('name')
            ->paginate($this->perPage);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function allPermissions()
    {
        return Permission::orderBy('name')->get();
    }

    #[Computed]
    public function allAccounts()
    {
        return User::orderBy('name')->get(['id', 'name', 'email']);
    }

    public function edit(): void
    {
        $this->authorize('update', $this->role);
        $this->name = $this->role->name;
        $this->description = $this->role->description;
        $this->permission_names = $this->role->permissions->pluck('name')->all();
        $this->user_ids = $this->role->users->pluck('id')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->authorize('update', $this->role);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->role->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'permission_names' => ['array'],
            'permission_names.*' => ['exists:permissions,name'],
            'user_ids' => ['array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $this->role->update(['name' => $validated['name'], 'description' => $validated['description']]);
        $this->role->syncPermissions($validated['permission_names'] ?? []);

        // Rattacher des utilisateurs = gestion des comptes : exige manage_users.
        if (! empty($validated['user_ids'])) {
            abort_unless(auth()->user()->can('manage_users'), 403);
        }

        $this->role->users()->sync($validated['user_ids'] ?? []);

        $this->showForm = false;
        unset($this->permissions);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->role);
        abort_if(in_array($this->role->name, ['Super administrateur', 'Administrateur', 'Éditeur', 'Commercial'], true), 403, 'Rôle système : suppression impossible.');
        $this->role->delete();

        $this->redirect(route('admin.users'), navigate: true);
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Détail du rôle</flux:heading>
            <flux:subheading>Permissions associées au rôle.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item :href="route('admin.users')" wire:navigate>Utilisateurs & rôles</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>{{ $role->name }}</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <div class="grid gap-4 lg:grid-cols-4">
        <div class="lg:col-span-1">
            <x-admin.card title="Rôle">
                <div class="flex items-center gap-4">
                    <span aria-hidden="true" class="flex h-14 w-14 shrink-0 items-center justify-center rounded-lg bg-cuivre/10 text-cuivre">
                        <flux:icon.shield-check class="size-8" />
                    </span>
                    <div class="min-w-0">
                        <div class="font-display text-xl font-bold text-anthracite dark:text-white">{{ $role->name }}</div>
                        <div class="text-sm text-zinc-500">{{ $this->permissions->total() }} permission(s) · {{ $role->users()->count() }} utilisateur(s)</div>
                    </div>
                </div>

                <p class="text-sm leading-relaxed text-zinc-600 dark:text-zinc-300">{{ $role->description ?: 'Aucune description.' }}</p>

                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-bordure border-dashed pt-4 text-sm">
                    <span class="text-xs text-zinc-400">Mis à jour {{ $role->updated_at?->diffForHumans() }}</span>
                    <div class="flex items-center gap-2">
                        <flux:button size="sm" variant="filled" icon="pencil" wire:click="edit">Modifier</flux:button>
                        <flux:button size="sm" variant="danger" icon="trash" wire:click="delete" wire:confirm="Supprimer ce rôle ?">Supprimer</flux:button>
                    </div>
                </div>
            </x-admin.card>
        </div>

        <div class="lg:col-span-3">
            <x-admin.card title="Permissions du rôle" :padded="false">
                <x-admin.toolbar search-placeholder="Rechercher une permission…">
                    <x-slot:filters>
                        <span class="text-sm text-zinc-500">{{ $this->permissions->total() }} permission(s)</span>
                    </x-slot:filters>
                </x-admin.toolbar>

                <div class="overflow-x-auto">
                    <flux:table :paginate="$this->permissions">
                        <flux:table.columns>
                            <flux:table.column>Permission</flux:table.column>
                            <flux:table.column>Description</flux:table.column>
                        </flux:table.columns>

                        <flux:table.rows>
                            @foreach ($this->permissions as $permission)
                                <flux:table.row wire:key="role-permission-{{ $permission->id }}">
                                    <flux:table.cell variant="strong">{{ $permission->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $permission->description ?: '—' }}</flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </div>
            </x-admin.card>
        </div>
    </div>

    <flux:modal wire:model="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">Modifier le rôle</flux:heading>
                <flux:subheading>Nom, description, permissions et utilisateurs assignés.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nom *</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Description</flux:label>
                <flux:textarea wire:model="description" rows="2" />
                <flux:error name="description" />
            </flux:field>

            <flux:field>
                <flux:label>Permissions</flux:label>
                <div class="grid gap-2 mt-1 max-h-48 overflow-y-auto">
                    @foreach($this->allPermissions as $permission)
                        <label class="flex items-start gap-2 text-sm">
                            <flux:checkbox wire:model="permission_names" :value="$permission->name" class="mt-0.5" />
                            <span class="min-w-0">
                                <span class="block">{{ $permission->name }}</span>
                                <span class="block text-xs text-zinc-500">{{ $permission->description }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="permission_names" />
            </flux:field>

            <flux:field>
                <flux:label>Utilisateurs assignés</flux:label>
                <div class="grid gap-2 mt-1 max-h-48 overflow-y-auto">
                    @foreach($this->allAccounts as $account)
                        <label class="flex items-center gap-2 text-sm">
                            <flux:checkbox wire:model="user_ids" :value="$account->id" />
                            <span class="min-w-0">
                                <span class="block truncate">{{ $account->name }}</span>
                                <span class="block truncate text-xs text-zinc-500">{{ $account->email }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
                <flux:error name="user_ids" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
