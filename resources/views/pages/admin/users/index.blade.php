<?php

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Utilisateurs')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    /** @var array<string> */
    public array $role_names = [];

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with('roles')
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($this->search).'%'])))
            ->orderBy('name')
            ->paginate(15);
    }

    #[Computed]
    public function roles()
    {
        return Role::orderBy('name')->get();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->reset(['editingId', 'name', 'email', 'password', 'role_names']);
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role_names = $user->roles->pluck('name')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $passwordRule = $this->editingId
            ? ['nullable', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols()]
            : ['required', 'string', Password::min(12)->letters()->mixedCase()->numbers()->symbols()];

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'password' => $passwordRule,
            'role_names' => ['array'],
            'role_names.*' => ['exists:roles,name'],
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $this->authorize('update', $user);
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            if (filled($validated['password'])) {
                $user->password = $validated['password'];
                $user->password_changed_at = null;
            }
            $user->save();
            $user->syncRoles($validated['role_names'] ?? []);
        } else {
            $this->authorize('create', User::class);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
            ]);
            $user->syncRoles($validated['role_names'] ?? []);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'email', 'password', 'role_names']);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        $user->delete();
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl">Utilisateurs</flux:heading>
            <flux:subheading>Comptes d’accès à l’administration et leurs rôles.</flux:subheading>
        </div>
        <flux:button variant="primary" wire:click="create" icon="plus">Nouvel utilisateur</flux:button>
    </div>

    <div class="mb-4 max-w-sm">
        <flux:input wire:model.live="search" type="search" placeholder="Nom ou email…" />
    </div>

    <flux:table :paginate="$this->users">
        <flux:table.columns>
            <flux:table.column>Nom</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Rôles</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->users as $user)
                <flux:table.row wire:key="user-row-{{ $user->id }}">
                    <flux:table.cell variant="strong">{{ $user->name }}</flux:table.cell>
                    <flux:table.cell>{{ $user->email }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-wrap gap-1">
                            @foreach($user->roles as $role)
                                <flux:badge size="sm" color="zinc">{{ $role->name }}</flux:badge>
                            @endforeach
                        </div>
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit({{ $user->id }})">Modifier</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $user->id }})" wire:confirm="Supprimer cet utilisateur ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</flux:heading>
                <flux:subheading>Identité, mot de passe et rôles.</flux:subheading>
            </div>

            <flux:field>
                <flux:label>Nom *</flux:label>
                <flux:input wire:model="name" type="text" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>Email *</flux:label>
                <flux:input wire:model="email" type="email" />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>Mot de passe {{ $editingId ? '(laisser vide pour conserver)' : '*' }}</flux:label>
                <flux:input wire:model="password" type="password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label>Rôles</flux:label>
                <div class="grid gap-2 mt-1">
                    @foreach($this->roles as $role)
                        <label class="flex items-center gap-2 text-sm">
                            <flux:checkbox wire:model="role_names" :value="$role->name" />
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
                <flux:error name="role_names" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
