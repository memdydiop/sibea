<?php

use App\Models\Activity;
use App\Models\User;
use App\Notifications\UserInvitation;
use Flux\Flux;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

    public string $statusFilter = '';

    public int $perPage = 15;

    public bool $showForm = false;

    public bool $showHistory = false;

    public ?int $historyUserId = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

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
            ->when($this->statusFilter === 'suspended', fn ($query) => $query->whereNotNull('suspended_at'))
            ->when($this->statusFilter === 'active', fn ($query) => $query->whereNull('suspended_at'))
            ->orderBy('name')
            ->paginate($this->perPage);
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

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->authorize('create', User::class);
        $this->reset(['editingId', 'name', 'email', 'role_names']);
        $this->role_names = Role::where('name', 'Commercial')->exists() ? ['Commercial'] : [];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role_names = $user->roles->pluck('name')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role_names' => ['array'],
            'role_names.*' => ['exists:roles,name'],
        ]);

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $this->authorize('update', $user);
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->save();
            $user->syncRoles($validated['role_names'] ?? []);
            $this->logActivity($user, 'updated', 'Compte modifié.');
        } else {
            $this->authorize('create', User::class);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => static::randomPassword(),
            ]);
            $user->syncRoles($validated['role_names'] ?? []);
            $user->notify(new UserInvitation);
            $this->logActivity($user, 'created', 'Compte créé, invitation envoyée.');

            Flux::toast(variant: 'success', text: 'Compte créé, invitation envoyée à '.$user->email.'.');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'email', 'role_names']);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        $user->delete();
        $this->logActivity($user, 'deleted', 'Compte supprimé.');
    }

    public function suspend(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('suspend', $user);
        $user->update(['suspended_at' => now()]);
        $this->logActivity($user, 'suspended', 'Compte suspendu.');

        Flux::toast(variant: 'success', text: 'Compte de '.$user->email.' suspendu.');
    }

    public function unsuspend(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('unsuspend', $user);
        $user->update(['suspended_at' => null]);
        $this->logActivity($user, 'unsuspended', 'Compte réactivé.');

        Flux::toast(variant: 'success', text: 'Compte de '.$user->email.' réactivé.');
    }

    public function resendInvitation(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        abort_if($user->password_changed_at !== null, 403);
        $user->notify(new UserInvitation);
        $this->logActivity($user, 'invitation_resent', 'Invitation renvoyée.');

        Flux::toast(variant: 'success', text: 'Invitation renvoyée à '.$user->email.'.');
    }

    #[Computed]
    public function historyUser(): ?User
    {
        return $this->historyUserId ? User::find($this->historyUserId) : null;
    }

    #[Computed]
    public function userHistory()
    {
        if (! $this->historyUserId) {
            return collect();
        }

        return Activity::query()
            ->with('actor')
            ->where('subject_type', (new User)->getMorphClass())
            ->where('subject_id', $this->historyUserId)
            ->latest()
            ->take(30)
            ->get();
    }

    public function openHistory(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('view', $user);
        $this->historyUserId = $user->id;
        $this->showHistory = true;
        unset($this->userHistory, $this->historyUser);
    }

    public function closeHistory(): void
    {
        $this->showHistory = false;
        $this->reset('historyUserId');
        unset($this->userHistory, $this->historyUser);
    }

    private function logActivity(User $user, string $action, ?string $description = null): void
    {
        Activity::record(auth()->user(), $user, $action, $description);
    }

    private static function randomPassword(): string
    {
        $sets = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            '0123456789',
            '!#$%&()*+,-.:;=?@[]_{}~',
        ];

        $password = array_map(fn ($set) => $set[random_int(0, strlen($set) - 1)], $sets);

        $all = implode('', $sets);

        for ($i = count($password); $i < 16; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Utilisateurs</flux:heading>
            <flux:subheading>Comptes d’accès à l’administration et leurs rôles.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Utilisateurs</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <x-admin.card title="Utilisateurs" :padded="false">

        <x-slot:actions>
            <flux:button variant="primary" size="sm" wire:click="create" icon="plus" aria-label="Nouvel utilisateur" tooltip="Nouvel utilisateur" />
        </x-slot:actions>

        <x-admin.toolbar search-placeholder="Nom ou email…">
            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actif</option>
                    <option value="suspended">Suspendu</option>
                </flux:select>
            </x-slot:filters>
        </x-admin.toolbar>

        <div class="overflow-x-auto">
            <flux:table :paginate="$this->users">
            <flux:table.columns>
                <flux:table.column>Nom</flux:table.column>
                <flux:table.column>Email</flux:table.column>
                <flux:table.column>Rôles</flux:table.column>
                <flux:table.column align="end">Actions</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @foreach ($this->users as $user)
                    <flux:table.row wire:key="user-row-{{ $user->id }}">
                        <flux:table.cell variant="strong">
                            <div class="flex items-center gap-2">
                                {{ $user->name }}
                                @if($user->suspended_at !== null)
                                    <flux:badge size="sm" color="red">Suspendu</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $user->email }}</flux:table.cell>
                        <flux:table.cell>
                            <div class="flex flex-wrap gap-1">
                                @foreach ($user->roles as $role)
                                    <flux:badge size="sm" color="zinc">{{ $role->name }}</flux:badge>
                                @endforeach
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex justify-end gap-1">
                                @if($user->suspended_at !== null)
                                    <flux:button size="xs" variant="filled" icon="play"
                                        wire:click="unsuspend({{ $user->id }})" wire:confirm="Réactiver ce compte ?"
                                        aria-label="Réactiver" tooltip="Réactiver" />
                                @else
                                    <flux:button size="xs" variant="filled" icon="pause"
                                        wire:click="suspend({{ $user->id }})" wire:confirm="Suspendre ce compte ?"
                                        :disabled="$user->is(auth()->user())"
                                        aria-label="Suspendre" tooltip="Suspendre" />
                                @endif
                                <flux:button size="xs" variant="filled" icon="clock"
                                    wire:click="openHistory({{ $user->id }})"
                                    aria-label="Historique" tooltip="Historique" />
                                <flux:button size="xs" variant="filled" icon="envelope"
                                    wire:click="resendInvitation({{ $user->id }})" wire:confirm="Renvoyer l’invitation ?"
                                    :disabled="$user->password_changed_at !== null"
                                    aria-label="Renvoyer l’invitation" tooltip="Renvoyer l’invitation" />
                                <flux:button size="xs" variant="filled" icon="pencil"
                                    wire:click="edit({{ $user->id }})"
                                    :disabled="$user->password_changed_at !== null && $user->suspended_at === null"
                                    aria-label="Modifier (suspendre d’abord si compte activé)" tooltip="Modifier (suspendre d’abord si compte activé)" />
                                <flux:button size="xs" variant="filled" color="red" icon="trash"
                                    wire:click="delete({{ $user->id }})" wire:confirm="Supprimer cet utilisateur ?"
                                    :disabled="$user->suspended_at === null"
                                    aria-label="Supprimer (compte suspendu uniquement)" tooltip="Supprimer (compte suspendu uniquement)" />
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
            </flux:table>
        </div>

    </x-admin.card>

    <flux:modal wire:model="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}</flux:heading>
                <flux:subheading>{{ $editingId ? 'Identité et rôles.' : 'Identité et rôles. Le mot de passe sera défini par l’utilisateur via son invitation.' }}</flux:subheading>
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

            @if($editingId)
                <p class="text-sm text-zinc-500">Le mot de passe ne peut être modifié que par son propriétaire (invitation, mot de passe oublié ou profil).</p>
            @endif

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

    <flux:modal wire:model="showHistory" class="md:w-[36rem]">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">Historique du compte</flux:heading>
                <flux:subheading>{{ $this->historyUser?->name }} ({{ $this->historyUser?->email }})</flux:subheading>
            </div>

            @if($this->userHistory->isEmpty())
                <p class="text-sm text-zinc-500">Aucune action enregistrée pour ce compte.</p>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($this->userHistory as $activity)
                        <li wire:key="activity-{{ $activity->id }}" class="flex items-center gap-3 py-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium">{{ $activity->description ?: $activity->action }}</div>
                                <div class="text-xs text-zinc-500">par {{ $activity->actor?->name ?: 'système' }} · {{ $activity->created_at?->diffForHumans() }}</div>
                            </div>
                            <flux:badge size="sm" color="zinc">{{ $activity->action }}</flux:badge>
                        </li>
                    @endforeach
                </ul>
            @endif

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="closeHistory">Fermer</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
