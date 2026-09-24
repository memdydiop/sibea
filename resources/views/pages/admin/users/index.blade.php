<?php

use App\Models\Activity;
use App\Models\User;
use App\Notifications\UserInvitation;
use Flux\Flux;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

new #[Layout('layouts::app')] #[Title('Utilisateurs & rôles')] class extends Component {
    use WithPagination;

    /** Rôles de la matrice seedée : leur suppression casserait les accès. */
    private const SYSTEM_ROLES = ['Super administrateur', 'Administrateur', 'Éditeur', 'Commercial'];

    public string $search = '';

    public string $statusFilter = '';

    public ?string $roleFilter = null;

    public int $perPage = 15;

    public bool $showForm = false;

    public bool $showHistory = false;

    public ?int $historyUserId = null;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    /** @var array<string> */
    public array $role_names = [];

    /** @var array<string> */
    public array $permission_names = [];

    public bool $showRoleForm = false;

    public ?int $editingRoleId = null;

    public string $role_name = '';

    public ?string $role_description = null;

    /** @var array<string> */
    public array $role_permission_names = [];

    /** @var array<int> */
    public array $role_user_ids = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', User::class) || auth()->user()->can('viewAny', Role::class), 403);
    }

    #[Computed]
    public function users()
    {
        return User::query()
            ->with(['roles', 'permissions'])
            ->when($this->search, fn($query) => $query->where(fn($q) => $q->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($this->search) . '%'])->orWhereRaw('LOWER(email) LIKE ?', ['%' . mb_strtolower($this->search) . '%'])))
            ->when($this->statusFilter === 'suspended', fn($query) => $query->whereNotNull('suspended_at'))
            ->when($this->statusFilter === 'active', fn($query) => $query->whereNull('suspended_at')->whereNotNull('password_changed_at'))
            ->when($this->statusFilter === 'invited', fn($query) => $query->whereNull('suspended_at')->whereNull('password_changed_at'))
            ->when($this->roleFilter, fn($query) => $query->whereHas('roles', fn($q) => $q->where('name', $this->roleFilter)))
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

    public function updatedRoleFilter(): void
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
        $this->reset(['editingId', 'name', 'email', 'role_names', 'permission_names']);
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
        $this->permission_names = $user->permissions->pluck('name')->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingId)],
            'role_names' => ['array'],
            'role_names.*' => ['exists:roles,name'],
            'permission_names' => ['array'],
            'permission_names.*' => ['exists:permissions,name'],
        ]);

        // Attribuer un accès à privilèges (gestion des comptes ou des rôles,
        // direct ou via un rôle) = acte d'administration des rôles : exige
        // manage_roles en plus de manage_users. Les rôles opérationnels
        // (ex. Commercial) restent attribuables avec manage_users seul.
        if ($this->assignsPrivilegedAccess($validated['role_names'] ?? [], $validated['permission_names'] ?? [])) {
            abort_unless(auth()->user()->can('manage_roles'), 403);
        }

        if ($this->editingId) {
            $user = User::findOrFail($this->editingId);
            $this->authorize('update', $user);
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->save();
            $user->syncRoles($validated['role_names'] ?? []);
            // Les permissions masquées (couvertes par les rôles) sont conservées :
            // seul le décochage explicite d'une permission visible la retire.
            $hidden = $user->permissions->pluck('name')->diff($this->assignablePermissionNames())->all();
            $user->syncPermissions(array_values(array_unique(array_merge(array_intersect($validated['permission_names'] ?? [], $this->assignablePermissionNames()), $hidden))));
            $this->logActivity($user, 'updated', 'Compte modifié.');
        } else {
            $this->authorize('create', User::class);
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => static::randomPassword(),
            ]);
            $user->syncRoles($validated['role_names'] ?? []);
            $user->syncPermissions(array_values(array_intersect($validated['permission_names'] ?? [], $this->assignablePermissionNames())));
            $user->notify(new UserInvitation());
            $this->logActivity($user, 'created', 'Compte créé, invitation envoyée.');

            Flux::toast(variant: 'success', text: 'Compte créé, invitation envoyée à ' . $user->email . '.');
        }

        $this->showForm = false;
        $this->reset(['editingId', 'name', 'email', 'role_names', 'permission_names']);
    }

    public function delete(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('delete', $user);
        abort_if($this->isLastRoleManager($user), 403, 'Dernier gestionnaire des rôles : suppression impossible.');
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->delete();
        $this->logActivity($user, 'deleted', 'Compte supprimé.');
    }

    #[Computed]
    public function roleCards()
    {
        return Role::query()
            ->with(['permissions', 'users'])
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function allPermissions()
    {
        return Permission::orderBy('name')->get();
    }

    /**
     * Permissions directes proposées : celles déjà couvertes par les
     * rôles sélectionnés sont exclues pour éviter les doublons.
     *
     * @return array<int, string>
     */
    protected function assignablePermissionNames(): array
    {
        $covered = Role::query()->whereIn('name', $this->role_names)->with('permissions')->get()->flatMap(fn(Role $role) => $role->permissions->pluck('name'))->unique()->all();

        return Permission::query()->whereNotIn('name', $covered)->orderBy('name')->pluck('name')->all();
    }

    #[Computed]
    public function assignablePermissions()
    {
        return Permission::query()->whereIn('name', $this->assignablePermissionNames())->orderBy('name')->get();
    }

    #[Computed]
    public function allAccounts()
    {
        return User::orderBy('name')->get(['id', 'name', 'email']);
    }

    public function createRole(): void
    {
        $this->authorize('create', Role::class);
        $this->reset(['editingRoleId', 'role_name', 'role_description', 'role_permission_names', 'role_user_ids']);
        $this->showRoleForm = true;
    }

    public function editRole(int $id): void
    {
        $role = Role::findOrFail($id);
        $this->authorize('update', $role);
        $this->editingRoleId = $role->id;
        $this->role_name = $role->name;
        $this->role_description = $role->description;
        $this->role_permission_names = $role->permissions->pluck('name')->all();
        $this->role_user_ids = $role->users->pluck('id')->all();
        $this->showRoleForm = true;
    }

    public function saveRole(): void
    {
        $validated = $this->validate([
            'role_name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($this->editingRoleId)],
            'role_description' => ['nullable', 'string', 'max:500'],
            'role_permission_names' => ['array'],
            'role_permission_names.*' => ['exists:permissions,name'],
            'role_user_ids' => ['array'],
            'role_user_ids.*' => ['exists:users,id'],
        ]);

        if ($this->editingRoleId) {
            $role = Role::findOrFail($this->editingRoleId);
            $this->authorize('update', $role);
            $role->update(['name' => $validated['role_name'], 'description' => $validated['role_description']]);
        } else {
            $this->authorize('create', Role::class);
            $role = Role::create(['name' => $validated['role_name'], 'description' => $validated['role_description'], 'guard_name' => 'web']);
        }

        // Rattacher des utilisateurs à un rôle = acte de gestion des comptes :
        // exige manage_users en plus de manage_roles.
        if (! empty($validated['role_user_ids'])) {
            abort_unless(auth()->user()->can('manage_users'), 403);
        }

        $role->syncPermissions($validated['role_permission_names'] ?? []);
        $role->users()->sync($validated['role_user_ids'] ?? []);

        $this->showRoleForm = false;
        $this->reset(['editingRoleId', 'role_name', 'role_description', 'role_permission_names', 'role_user_ids']);
        unset($this->roleCards);
    }

    public function deleteRole(int $id): void
    {
        $role = Role::findOrFail($id);
        $this->authorize('delete', $role);
        abort_if(in_array($role->name, self::SYSTEM_ROLES, true), 403, 'Rôle système : suppression impossible.');
        $role->delete();
        unset($this->roleCards);
    }

    public function suspend(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('suspend', $user);
        abort_if($this->isLastRoleManager($user), 403, 'Dernier gestionnaire des rôles : suspension impossible.');
        $user->update(['suspended_at' => now()]);

        // Révocation immédiate : sessions DB + remember-me, sinon le compte
        // suspendu reste connecté jusqu'à expiration naturelle du cookie.
        DB::table('sessions')->where('user_id', $user->id)->delete();
        $user->forceFill(['remember_token' => null])->save();

        $this->logActivity($user, 'suspended', 'Compte suspendu.');

        Flux::toast(variant: 'success', text: 'Compte de ' . $user->email . ' suspendu.');
    }

    public function unsuspend(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('unsuspend', $user);
        $user->update(['suspended_at' => null]);
        $this->logActivity($user, 'unsuspended', 'Compte réactivé.');

        Flux::toast(variant: 'success', text: 'Compte de ' . $user->email . ' réactivé.');
    }

    public function resendInvitation(int $id): void
    {
        $user = User::findOrFail($id);
        $this->authorize('update', $user);
        abort_if($user->password_changed_at !== null, 403);
        abort_if($user->suspended_at !== null, 403);

        // Anti-spam : 3 renvois / 10 min par compte ciblé.
        $key = 'invitation.resend:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            Flux::toast(variant: 'error', text: 'Trop de renvois. Réessayez dans quelques minutes.');

            return;
        }
        RateLimiter::hit($key, 600);

        $user->notify(new UserInvitation());
        $this->logActivity($user, 'invitation_resent', 'Invitation renvoyée.');

        Flux::toast(variant: 'success', text: 'Invitation renvoyée à ' . $user->email . '.');
    }

    #[Computed]
    public function historyUser(): ?User
    {
        return $this->historyUserId ? User::find($this->historyUserId) : null;
    }

    #[Computed]
    public function userHistory()
    {
        if (!$this->historyUserId) {
            return collect();
        }

        return Activity::query()
            ->with('actor')
            ->where('subject_type', new User()->getMorphClass())
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

    /**
     * @param  array<int, string>  $roleNames
     * @param  array<int, string>  $permissionNames
     */
    private function assignsPrivilegedAccess(array $roleNames, array $permissionNames): bool
    {
        if (array_intersect($permissionNames, ['manage_users', 'manage_roles']) !== []) {
            return true;
        }

        if ($roleNames === []) {
            return false;
        }

        return Role::query()->whereIn('name', $roleNames)
            ->whereHas('permissions', fn ($query) => $query->whereIn('name', ['manage_users', 'manage_roles']))
            ->exists();
    }

    /**
     * Dernier détenteur actif du pouvoir sur les rôles : le neutraliser
     * (suspension/suppression) verrouillerait l'administration des accès.
     */
    private function isLastRoleManager(User $user): bool
    {
        if (! $user->hasRole('Super administrateur') && ! $user->can('manage_roles')) {
            return false;
        }

        return ! User::query()->whereKeyNot($user->getKey())->whereNull('suspended_at')
            ->where(fn ($query) => $query
                ->whereHas('roles.permissions', fn ($sub) => $sub->where('name', 'manage_roles'))
                ->orWhereHas('permissions', fn ($sub) => $sub->where('name', 'manage_roles')))
            ->exists();
    }

    private static function randomPassword(): string
    {
        $sets = ['abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '0123456789', '!#$%&()*+,-.:;=?@[]_{}~'];

        $password = array_map(fn($set) => $set[random_int(0, strlen($set) - 1)], $sets);

        $all = implode('', $sets);

        for ($i = count($password); $i < 16; $i++) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }
};
?>

<div class="p-6 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Utilisateurs & rôles</flux:heading>
            <flux:subheading>Comptes d’accès à l’administration, rôles et permissions.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Utilisateurs & rôles</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    @can('viewAny', Spatie\Permission\Models\Role::class)
        <div class="mb-4 flex items-center justify-between gap-4">
            <flux:heading size="lg" class="mb-0!">Rôles</flux:heading>
            <flux:button variant="primary" size="sm" wire:click="createRole" icon="plus">Nouveau rôle</flux:button>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->roleCards as $roleCard)
                <x-admin.card wire:key="role-card-{{ $roleCard->id }}">

                    <div class="flex flex-col gap-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span aria-hidden="true"
                                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-cuivre/10 text-cuivre">
                                    <flux:icon.shield-check class="size-6" />
                                </span>
                                <div>
                                    <div class="font-display font-bold text-anthracite dark:text-white">
                                        {{ $roleCard->name }}</div>
                                </div>
                            </div>
                            <flux:button href="{{ route('admin.roles.show', $roleCard) }}" wire:navigate variant="filled"
                                size="sm">
                                <flux:icon.eye variant="micro" />
                            </flux:button>
                        </div>

                        <div class="text-sm text-zinc-500">{{ $roleCard->description ?: 'Aucune description.' }}</div>

                        <ul class="space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                            @forelse ($roleCard->permissions->take(4) as $permission)
                                <li class="flex items-center gap-2">
                                    <flux:icon.check class="size-4 shrink-0 text-cuivre" />
                                    <span class="truncate">{{ $permission->name }}</span>
                                </li>
                            @empty
                                <li class="text-xs text-zinc-400">Aucune permission.</li>
                            @endforelse
                            @if ($roleCard->permissions_count > 4)
                                <li class="text-xs text-zinc-400">+{{ $roleCard->permissions_count - 4 }} autre(s)…</li>
                            @endif
                        </ul>
                    </div>

                    <div class="flex items-center justify-between border-t border-bordure border-dashed pt-3 text-sm">
                        <div class="flex items-center gap-2">
                            <div class="flex -space-x-2">
                                @foreach ($roleCard->users->take(4) as $member)
                                    <span aria-hidden="true" title="{{ $member->name }}"
                                        class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-white bg-nuit text-[10px] font-bold text-cuivre dark:border-zinc-900">
                                        {{ $member->initials() }}
                                    </span>
                                @endforeach
                            </div>
                            <span class="text-zinc-500">Total {{ $roleCard->users_count }} utilisateur(s)</span>
                        </div>
                    </div>
                    
                </x-admin.card>
            @endforeach
        </div>

        <flux:modal wire:model="showRoleForm" class="md:w-[32rem]">
            <form wire:submit="saveRole" class="space-y-5">
                <div>
                    <flux:heading size="lg">{{ $editingRoleId ? 'Modifier le rôle' : 'Nouveau rôle' }}</flux:heading>
                    <flux:subheading>Nom, description, permissions et utilisateurs assignés.</flux:subheading>
                </div>

                <flux:field>
                    <flux:label>Nom *</flux:label>
                    <flux:input wire:model="role_name" type="text" />
                    <flux:error name="role_name" />
                </flux:field>

                <flux:field>
                    <flux:label>Description</flux:label>
                    <flux:textarea wire:model="role_description" rows="2"
                        placeholder="Ex. Gère les contenus du site, sans accès aux utilisateurs." />
                    <flux:error name="role_description" />
                </flux:field>

                <flux:field>
                    <flux:label>Permissions</flux:label>
                    <div class="grid gap-2 mt-1 max-h-48 overflow-y-auto">
                        @foreach ($this->allPermissions as $permission)
                            <label class="flex items-start gap-2 text-sm">
                                <flux:checkbox wire:model="role_permission_names" :value="$permission->name"
                                    class="mt-0.5" />
                                <span class="min-w-0">
                                    <span class="block">{{ $permission->name }}</span>
                                    <span class="block text-xs text-zinc-500">{{ $permission->description }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="role_permission_names" />
                </flux:field>

                <flux:field>
                    <flux:label>Utilisateurs assignés</flux:label>
                    <div class="grid gap-2 mt-1 max-h-48 overflow-y-auto">
                        @foreach ($this->allAccounts as $account)
                            <label class="flex items-center gap-2 text-sm">
                                <flux:checkbox wire:model="role_user_ids" :value="$account->id" />
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $account->name }}</span>
                                    <span class="block truncate text-xs text-zinc-500">{{ $account->email }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="role_user_ids" />
                </flux:field>

                <div class="flex gap-2">
                    <flux:spacer />
                    <flux:button variant="ghost" wire:click="$set('showRoleForm', false)">Annuler</flux:button>
                    <flux:button type="submit" variant="primary">Enregistrer</flux:button>
                </div>
            </form>
        </flux:modal>
    @endcan

    @can('viewAny', App\Models\User::class)
        <x-admin.card title="Utilisateurs" :padded="false">

            <x-slot:actions>
                <flux:button variant="primary" size="sm" wire:click="create" icon="plus"
                    aria-label="Nouvel utilisateur" tooltip="Nouvel utilisateur" />
            </x-slot:actions>

            <x-admin.toolbar search-placeholder="Nom ou email…">
                <x-slot:filters>
                    <flux:select wire:model.live="statusFilter" size="sm">
                        <option value="">Tous les statuts</option>
                        <option value="active">Actif</option>
                        <option value="invited">Invitation en attente</option>
                        <option value="suspended">Suspendu</option>
                    </flux:select>
                    <flux:select wire:model.live="roleFilter" size="sm">
                        <option value="">Tous les rôles</option>
                        @foreach ($this->roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </flux:select>
                </x-slot:filters>
            </x-admin.toolbar>

            <div class="overflow-x-auto">
                <flux:table :paginate="$this->users">
                    <flux:table.columns>
                        <flux:table.column>Utilisateur</flux:table.column>
                        <flux:table.column>Rôles</flux:table.column>
                        <flux:table.column>Statut</flux:table.column>
                        <flux:table.column>Mis à jour</flux:table.column>
                        <flux:table.column align="end">Actions</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @foreach ($this->users as $user)
                            <flux:table.row wire:key="user-row-{{ $user->id }}">
                                <flux:table.cell variant="strong">
                                    <div class="flex items-center gap-3">
                                        <span aria-hidden="true"
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-nuit font-display text-sm font-bold text-cuivre">
                                            {{ $user->initials() }}
                                        </span>
                                        <span class="min-w-0">
                                            <span class="block truncate">{{ $user->name }}</span>
                                            <span
                                                class="block truncate text-xs font-normal text-zinc-500">{{ $user->email }}</span>
                                        </span>
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($user->roles as $role)
                                            <flux:badge size="sm" color="zinc">{{ $role->name }}</flux:badge>
                                        @endforeach
                                        @if ($user->permissions->isNotEmpty())
                                            <flux:badge size="sm" color="amber"
                                                tooltip="{{ $user->permissions->pluck('name')->join(', ') }}">
                                                +{{ $user->permissions->count() }} directe(s)</flux:badge>
                                        @endif
                                    </div>
                                </flux:table.cell>
                                <flux:table.cell>
                                    @if ($user->suspended_at !== null)
                                        <flux:badge size="sm" color="red">Suspendu</flux:badge>
                                    @elseif ($user->password_changed_at === null)
                                        <flux:badge size="sm" color="amber">Invitation en attente</flux:badge>
                                    @else
                                        <flux:badge size="sm" color="green">Actif</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>
                                    <span
                                        class="whitespace-nowrap text-xs text-zinc-500">{{ $user->updated_at?->diffForHumans() }}</span>
                                </flux:table.cell>
                                <flux:table.cell>
                                    <div class="flex justify-end gap-1">
                                        @if ($user->suspended_at !== null)
                                            <flux:button size="xs" variant="filled" icon="play"
                                                wire:click="unsuspend({{ $user->id }})"
                                                wire:confirm="Réactiver ce compte ?" aria-label="Réactiver"
                                                tooltip="Réactiver" />
                                        @else
                                            <flux:button size="xs" variant="filled" icon="pause"
                                                wire:click="suspend({{ $user->id }})"
                                                wire:confirm="Suspendre ce compte ?" :disabled="$user->is(auth()->user())"
                                                aria-label="Suspendre" tooltip="Suspendre" />
                                        @endif
                                        <flux:button size="xs" variant="filled" icon="clock"
                                            wire:click="openHistory({{ $user->id }})" aria-label="Historique"
                                            tooltip="Historique" />
                                        <flux:button size="xs" variant="filled" icon="envelope"
                                            wire:click="resendInvitation({{ $user->id }})"
                                            wire:confirm="Renvoyer l’invitation ?"
                                            :disabled="$user->password_changed_at !== null"
                                            aria-label="Renvoyer l’invitation" tooltip="Renvoyer l’invitation" />
                                        <flux:button size="xs" variant="filled" icon="pencil"
                                            wire:click="edit({{ $user->id }})"
                                            :disabled="$user->password_changed_at !== null && $user->suspended_at === null"
                                            aria-label="Modifier (suspendre d’abord si compte activé)"
                                            tooltip="Modifier (suspendre d’abord si compte activé)" />
                                        <flux:button size="xs" variant="filled" color="red" icon="trash"
                                            wire:click="delete({{ $user->id }})"
                                            wire:confirm="Supprimer cet utilisateur ?"
                                            :disabled="$user->suspended_at === null"
                                            aria-label="Supprimer (compte suspendu uniquement)"
                                            tooltip="Supprimer (compte suspendu uniquement)" />
                                    </div>
                                </flux:table.cell>
                            </flux:table.row>
                        @endforeach
                    </flux:table.rows>
                </flux:table>
            </div>

        </x-admin.card>
    @endcan

    <flux:modal wire:model="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">{{ $editingId ? 'Modifier l’utilisateur' : 'Nouvel utilisateur' }}
                </flux:heading>
                <flux:subheading>
                    {{ $editingId ? 'Identité et rôles.' : 'Identité et rôles. Le mot de passe sera défini par l’utilisateur via son invitation.' }}
                </flux:subheading>
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

            @if ($editingId)
                <p class="text-sm text-zinc-500">Le mot de passe ne peut être modifié que par son propriétaire
                    (invitation, mot de passe oublié ou profil).</p>
            @endif

            <flux:field>
                <flux:label>Rôles</flux:label>
                <div class="grid gap-2 mt-1">
                    @foreach ($this->roles as $role)
                        <label class="flex items-center gap-2 text-sm">
                            <flux:checkbox wire:model="role_names" :value="$role->name" />
                            {{ $role->name }}
                        </label>
                    @endforeach
                </div>
                <flux:error name="role_names" />
            </flux:field>

            <flux:field>
                <flux:label>Permissions directes (hors rôle)</flux:label>
                <flux:description>Cumulées avec celles des rôles. Celles déjà couvertes par les rôles cochés sont
                    masquées.</flux:description>
                <div class="grid gap-2 mt-1 max-h-48 overflow-y-auto">
                    @foreach ($this->assignablePermissions as $permission)
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

            @if ($this->userHistory->isEmpty())
                <p class="text-sm text-zinc-500">Aucune action enregistrée pour ce compte.</p>
            @else
                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach ($this->userHistory as $activity)
                        <li wire:key="activity-{{ $activity->id }}" class="flex items-center gap-3 py-2.5">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium">{{ $activity->description ?: $activity->action }}
                                </div>
                                <div class="text-xs text-zinc-500">par {{ $activity->actor?->name ?: 'système' }} ·
                                    {{ $activity->created_at?->diffForHumans() }}@if (! empty($activity->meta['ip'])) · IP {{ $activity->meta['ip'] }}@endif</div>
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
