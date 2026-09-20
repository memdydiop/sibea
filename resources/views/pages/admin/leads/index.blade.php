<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\RequestType;
use App\Events\LeadCreated;
use App\Models\Lead;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::app')] #[Title('Prospects')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public ?int $sectorFilter = null;

    public int $perPage = 15;

    public bool $showForm = false;

    public bool $showCreate = false;

    public ?int $editingId = null;

    public string $status = 'nouveau';

    public ?int $assigned_to = null;

    public ?string $notes = null;

    public string $name = '';

    public ?string $company = null;

    public string $email = '';

    public ?string $phone = null;

    public ?string $residence_country = null;

    public ?string $target_territory = null;

    public ?int $sector_id = null;

    public string $request_type = 'information';

    public ?string $budget = null;

    public string $message = '';

    public string $source = 'site';

    public function mount(): void
    {
        $this->authorize('viewAny', Lead::class);
    }

    #[Computed]
    public function sectors()
    {
        return Sector::ordered()->get();
    }

    #[Computed]
    public function users()
    {
        return User::orderBy('name')->get();
    }

    #[Computed]
    public function leads()
    {
        return Lead::query()
            ->with(['sector', 'assignedTo'])
            ->when($this->search, fn ($query) => $query->where(fn ($q) => $q
                ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($this->search).'%'])
                ->orWhereRaw('LOWER(email) LIKE ?', ['%'.mb_strtolower($this->search).'%'])))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->sectorFilter, fn ($query) => $query->where('sector_id', $this->sectorFilter))
            ->latest()
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

    public function updatedSectorFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function editingLead(): ?Lead
    {
        return $this->editingId ? Lead::with('sector')->find($this->editingId) : null;
    }

    #[Computed]
    public function activities()
    {
        if (! $this->editingId) {
            return collect();
        }

        return Lead::find($this->editingId)?->activities()->with('user')->take(20)->get() ?? collect();
    }

    public function edit(int $id): void
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('update', $lead);
        $this->editingId = $lead->id;
        $this->status = $lead->status->value;
        $this->assigned_to = $lead->assigned_to;
        $this->notes = $lead->notes;
        $this->showForm = true;
    }

    public function save(): void
    {
        $lead = Lead::findOrFail($this->editingId);
        $this->authorize('update', $lead);

        $validated = $this->validate([
            'status' => ['required', Rule::enum(LeadStatus::class)],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $oldStatus = $lead->status->value;
        $oldAssignee = $lead->assigned_to;

        $lead->update([
            'status' => LeadStatus::from($validated['status']),
            'assigned_to' => $validated['assigned_to'],
            'notes' => $validated['notes'],
        ]);

        if ($oldStatus !== $lead->status->value) {
            $lead->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'status_changed',
                'description' => 'Statut : '.$oldStatus.' → '.$lead->status->value.'.',
            ]);
        }

        if ($oldAssignee !== $lead->assigned_to) {
            $assignee = $lead->assignedTo?->name ?? 'personne';
            $lead->activities()->create([
                'user_id' => auth()->id(),
                'action' => 'assigned',
                'description' => 'Assigné à '.$assignee.'.',
            ]);
        }

        $this->showForm = false;
        $this->reset(['editingId', 'assigned_to', 'notes']);
        $this->status = LeadStatus::Nouveau->value;
    }

    public function create(): void
    {
        $this->authorize('create', Lead::class);
        $this->reset(['name', 'company', 'email', 'phone', 'residence_country', 'target_territory', 'sector_id', 'budget', 'message']);
        $this->request_type = RequestType::Information->value;
        $this->source = LeadSource::Site->value;
        $this->showCreate = true;
    }

    public function store(): void
    {
        $this->authorize('create', Lead::class);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'residence_country' => ['nullable', 'string', 'max:255'],
            'target_territory' => ['nullable', 'string', 'max:255'],
            'sector_id' => ['required', 'exists:sectors,id'],
            'request_type' => ['required', Rule::enum(RequestType::class)],
            'budget' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
            'source' => ['required', Rule::enum(LeadSource::class)],
        ]);

        $lead = Lead::create($validated + [
            'status' => LeadStatus::Nouveau,
            'source' => LeadSource::from($validated['source']),
        ]);

        $lead->activities()->create([
            'user_id' => auth()->id(),
            'action' => 'created',
            'description' => 'Prospect créé manuellement par '.auth()->user()->name.'.',
        ]);

        LeadCreated::dispatch($lead);

        $this->showCreate = false;
        $this->reset(['name', 'company', 'email', 'phone', 'residence_country', 'target_territory', 'sector_id', 'budget', 'message']);
        $this->request_type = RequestType::Information->value;
        $this->source = LeadSource::Site->value;
    }

    public function delete(int $id): void
    {
        $lead = Lead::findOrFail($id);
        $this->authorize('delete', $lead);
        $lead->delete();
    }

    public function purge(): void
    {
        abort_unless(auth()->user()->can('manage_leads'), 403);
        Artisan::call('leads:purge');
    }
};
?>

<div class="p-6">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <flux:heading size="xl" class="mb-0!">Prospects</flux:heading>
            <flux:subheading>Demandes reçues depuis le site public.</flux:subheading>
        </div>
        <flux:breadcrumbs>
            <flux:breadcrumbs.item :href="route('admin.dashboard')" wire:navigate>Dashboard</flux:breadcrumbs.item>
            <flux:breadcrumbs.item>Prospects</flux:breadcrumbs.item>
        </flux:breadcrumbs>
    </div>

    <x-admin.card title="Prospects" :padded="false">

        <x-slot:actions>
            <flux:button variant="ghost" size="sm" wire:click="purge" wire:confirm="Purger les prospects de plus de 3 ans ?" aria-label="Purger les prospects de plus de 3 ans" tooltip="Purger les prospects de plus de 3 ans">Purger +3 ans</flux:button>
            <flux:button variant="primary" size="sm" wire:click="create" icon="plus" aria-label="Nouveau prospect" tooltip="Nouveau prospect" />
        </x-slot:actions>

        <x-admin.toolbar search-placeholder="Nom ou email…">
            <x-slot:filters>
                <flux:select wire:model.live="statusFilter" size="sm">
                    <option value="">Tous les statuts</option>
                    @foreach(LeadStatus::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="sectorFilter" size="sm">
                    <option value="">Tous les secteurs</option>
                    @foreach($this->sectors as $sector)
                        <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                    @endforeach
                </flux:select>
            </x-slot:filters>
        </x-admin.toolbar>

        <div class="overflow-x-auto">
            <flux:table :paginate="$this->leads">
        <flux:table.columns>
            <flux:table.column>Nom</flux:table.column>
            <flux:table.column>Email</flux:table.column>
            <flux:table.column>Secteur</flux:table.column>
            <flux:table.column>Demande</flux:table.column>
            <flux:table.column>Statut</flux:table.column>
            <flux:table.column>Assigné à</flux:table.column>
            <flux:table.column align="end">Actions</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach($this->leads as $lead)
                <flux:table.row wire:key="lead-row-{{ $lead->id }}">
                    <flux:table.cell variant="strong">{{ $lead->name }}</flux:table.cell>
                    <flux:table.cell>{{ $lead->email }}</flux:table.cell>
                    <flux:table.cell>{{ $lead->sector?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>{{ $lead->request_type?->label() ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="{{ $lead->status === \App\Enums\LeadStatus::Nouveau ? 'blue' : 'zinc' }}">
                            {{ $lead->status?->label() ?? '—' }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $lead->assignedTo?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-1">
                            <flux:button size="xs" variant="filled" icon="pencil" wire:click="edit({{ $lead->id }})" aria-label="Modifier" tooltip="Modifier" />
                            <flux:button size="xs" variant="filled" color="red" icon="trash" wire:click="delete({{ $lead->id }})" wire:confirm="Supprimer ce prospect ?" aria-label="Supprimer" tooltip="Supprimer" />
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
                <flux:heading size="lg">Traiter le prospect</flux:heading>
                <flux:subheading>Statut, assignation et notes internes.</flux:subheading>
            </div>

            @if($this->editingLead)
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-4 text-sm space-y-1">
                    <div class="font-semibold">
                        {{ $this->editingLead->name }}@if($this->editingLead->company) — {{ $this->editingLead->company }}@endif
                    </div>
                    <div class="text-zinc-500">
                        {{ $this->editingLead->email }}@if($this->editingLead->phone) · {{ $this->editingLead->phone }}@endif
                    </div>
                    <div class="text-zinc-500">
                        {{ $this->editingLead->sector?->name ?? 'Secteur non précisé' }} · {{ $this->editingLead->request_type?->label() ?? '—' }}
                    </div>
                    @if($this->editingLead->residence_country || $this->editingLead->target_territory)
                        <div class="text-zinc-500">
                            Résidence : {{ $this->editingLead->residence_country ?? '—' }} · Territoire ciblé : {{ $this->editingLead->target_territory ?? '—' }}
                        </div>
                    @endif
                    @if($this->editingLead->budget)
                        <div class="text-zinc-500">Budget : {{ $this->editingLead->budget }}</div>
                    @endif
                    <div class="whitespace-pre-line pt-2 text-zinc-600 dark:text-zinc-400">{{ $this->editingLead->message }}</div>
                </div>
            @endif

            <flux:field>
                <flux:label>Statut *</flux:label>
                <flux:select wire:model="status">
                    @foreach(LeadStatus::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="status" />
            </flux:field>

            <flux:field>
                <flux:label>Assigné à</flux:label>
                <flux:select wire:model="assigned_to">
                    <option value="">Non assigné</option>
                    @foreach($this->users as $user)
                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </flux:select>
                <flux:error name="assigned_to" />
            </flux:field>

            <flux:field>
                <flux:label>Notes internes</flux:label>
                <flux:textarea wire:model="notes" rows="4" />
                <flux:error name="notes" />
            </flux:field>

            @if($this->activities->isNotEmpty())
                <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-4">
                    <div class="text-sm font-semibold mb-3">Historique</div>
                    <ul class="space-y-2 text-sm">
                        @foreach($this->activities as $activity)
                            <li wire:key="activity-{{ $activity->id }}" class="flex flex-col">
                                <span>{{ $activity->description }}</span>
                                <span class="text-xs text-zinc-500">
                                    {{ $activity->created_at->format('d/m/Y H:i') }}
                                    @if($activity->user) — {{ $activity->user->name }} @endif
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showCreate" class="md:w-[36rem]">
        <form wire:submit="store" class="space-y-5">
            <div>
                <flux:heading size="lg">Nouveau prospect</flux:heading>
                <flux:subheading>Saisie manuelle (téléphone, email, visite).</flux:subheading>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Nom complet *</flux:label>
                    <flux:input wire:model="name" type="text" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>Société</flux:label>
                    <flux:input wire:model="company" type="text" />
                    <flux:error name="company" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Email *</flux:label>
                    <flux:input wire:model="email" type="email" />
                    <flux:error name="email" />
                </flux:field>

                <flux:field>
                    <flux:label>Téléphone</flux:label>
                    <flux:input wire:model="phone" type="tel" />
                    <flux:error name="phone" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Pays de résidence</flux:label>
                    <flux:input wire:model="residence_country" type="text" placeholder="Ex. Côte d’Ivoire, France…" />
                    <flux:error name="residence_country" />
                </flux:field>

                <flux:field>
                    <flux:label>Territoire ciblé</flux:label>
                    <flux:input wire:model="target_territory" type="text" placeholder="Ex. Abidjan, Cocody" />
                    <flux:error name="target_territory" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Secteur concerné *</flux:label>
                    <flux:select wire:model="sector_id">
                        <option value="">Sélectionnez un secteur</option>
                        @foreach($this->sectors as $sector)
                            <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="sector_id" />
                </flux:field>

                <flux:field>
                    <flux:label>Type de demande *</flux:label>
                    <flux:select wire:model="request_type">
                        @foreach(RequestType::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="request_type" />
                </flux:field>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Budget indicatif</flux:label>
                    <flux:input wire:model="budget" type="text" />
                    <flux:error name="budget" />
                </flux:field>

                <flux:field>
                    <flux:label>Source *</flux:label>
                    <flux:select wire:model="source">
                        @foreach(LeadSource::cases() as $case)
                            <option value="{{ $case->value }}">{{ $case->label() }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="source" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label>Message *</flux:label>
                <flux:textarea wire:model="message" rows="4" />
                <flux:error name="message" />
            </flux:field>

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showCreate', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
