<?php

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\Sector;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
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

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $status = 'nouveau';

    public ?int $assigned_to = null;

    public ?string $notes = null;

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
            ->paginate(15);
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
            'status' => ['required', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ]);

        $lead->update([
            'status' => LeadStatus::from($validated['status']),
            'assigned_to' => $validated['assigned_to'],
            'notes' => $validated['notes'],
        ]);

        $this->showForm = false;
        $this->reset(['editingId', 'assigned_to', 'notes']);
        $this->status = LeadStatus::Nouveau->value;
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
            <flux:heading size="xl">Prospects</flux:heading>
            <flux:subheading>Demandes reçues depuis le site public.</flux:subheading>
        </div>
        <flux:button variant="ghost" wire:click="purge" wire:confirm="Purger les prospects de plus de 3 ans ?">Purger +3 ans</flux:button>
    </div>

    <div class="grid sm:grid-cols-3 gap-3 mb-4">
        <flux:input wire:model.live="search" type="search" placeholder="Nom ou email…" />
        <flux:select wire:model.live="statusFilter">
            <option value="">Tous les statuts</option>
            @foreach(LeadStatus::cases() as $case)
                <option value="{{ $case->value }}">{{ $case->label() }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="sectorFilter">
            <option value="">Tous les secteurs</option>
            @foreach($this->sectors as $sector)
                <option value="{{ $sector->id }}">{{ $sector->name }}</option>
            @endforeach
        </flux:select>
    </div>

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
                    <flux:table.cell>{{ $lead->request_type->label() }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge size="sm" color="{{ $lead->status === \App\Enums\LeadStatus::Nouveau ? 'blue' : 'zinc' }}">
                            {{ $lead->status->label() }}
                        </flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>{{ $lead->assignedTo?->name ?? '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <div class="flex justify-end gap-2">
                            <flux:button size="sm" wire:click="edit({{ $lead->id }})">Traiter</flux:button>
                            <flux:button size="sm" variant="danger" wire:click="delete({{ $lead->id }})" wire:confirm="Supprimer ce prospect ?">Supprimer</flux:button>
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal wire:model="showForm" class="md:w-[32rem]">
        <form wire:submit="save" class="space-y-5">
            <div>
                <flux:heading size="lg">Traiter le prospect</flux:heading>
                <flux:subheading>Statut, assignation et notes internes.</flux:subheading>
            </div>

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

            <div class="flex gap-2">
                <flux:spacer />
                <flux:button variant="ghost" wire:click="$set('showForm', false)">Annuler</flux:button>
                <flux:button type="submit" variant="primary">Enregistrer</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
